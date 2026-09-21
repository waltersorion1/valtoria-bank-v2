<?php
declare(strict_types=1);

final class TransferService
{
    public function __construct(private PDO $pdo) {}

    public function quote(int $amountCents): array
    {
        requireFeature($this->pdo, 'features.transfers', 'Transfers are temporarily unavailable.');
        $cfg = config('financial')['transfer'];
        if ($amountCents < $cfg['minimum_cents'] || $amountCents > $cfg['maximum_cents']) throw new InvalidArgumentException('Transfer amount is outside the available limits.');
        if ($cfg['step_up_cents'] > 0 && $amountCents >= $cfg['step_up_cents']) throw new RuntimeException('This transfer requires step-up verification, which is not enabled for this environment.');
        return ['amount_cents' => $amountCents, 'fee_cents' => $cfg['fee_flat_cents'], 'total_cents' => $amountCents + $cfg['fee_flat_cents']];
    }

    public function transfer(int $userId, int $beneficiaryId, int $amountCents, string $memo, string $idempotencyKey): array
    {
        requireFeature($this->pdo, 'features.transfers', 'Transfers are temporarily unavailable.');
        $financial = new FinancialService($this->pdo);
        if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) return $existing;
        $quote = $this->quote($amountCents);
        $this->pdo->beginTransaction();
        try {
            if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) { $this->pdo->commit(); return $existing; }
            $source = $financial->accountForUser($userId, false);
            $beneficiaryStmt = $this->pdo->prepare('SELECT b.*, a.account_id AS destination_account_id, a.user_id AS destination_user_id FROM beneficiaries b JOIN accounts a ON a.account_number = b.destination_account_number WHERE b.beneficiary_id = ? AND b.user_id = ? AND b.status = \'active\'');
            $beneficiaryStmt->execute([$beneficiaryId, $userId]);
            $beneficiary = $beneficiaryStmt->fetch();
            if (!$beneficiary) throw new RuntimeException('Beneficiary is not available.');
            if ((int) $beneficiary['destination_account_id'] === (int) $source['account_id']) throw new RuntimeException('You cannot transfer to your own account.');
            $accountIds = [(int) $source['account_id'], (int) $beneficiary['destination_account_id']];
            sort($accountIds, SORT_NUMERIC);
            $accountLocks = $this->pdo->prepare('SELECT account_id, balance_cents FROM accounts WHERE account_id IN (?, ?) ORDER BY account_id FOR UPDATE');
            $accountLocks->execute($accountIds);
            $lockedBalances = [];
            foreach ($accountLocks->fetchAll() as $locked) $lockedBalances[(int) $locked['account_id']] = (int) $locked['balance_cents'];
            $source['balance_cents'] = $lockedBalances[(int) $source['account_id']] ?? throw new RuntimeException('Source account is unavailable.');
            if ((int) $source['balance_cents'] < $quote['total_cents']) throw new RuntimeException('Insufficient available balance.');
            $daily = $this->pdo->prepare('SELECT COALESCE(SUM(amount_cents + fee_cents),0) FROM transfers WHERE user_id = ? AND status = \'completed\' AND initiated_at >= UTC_DATE()');
            $daily->execute([$userId]);
            if ((int) $daily->fetchColumn() + $quote['total_cents'] > config('financial')['transfer']['daily_limit_cents']) throw new RuntimeException('Daily transfer limit exceeded.');
            $transaction = $financial->createTransaction($userId, (int) $source['account_id'], 'card_transfer', $amountCents, $quote['fee_cents'], 'completed', $memo !== '' ? $memo : 'Card-to-card transfer', $idempotencyKey, ['beneficiary_id' => $beneficiaryId]);
            $stmt = $this->pdo->prepare('INSERT INTO transfers (financial_transaction_id, user_id, source_account_id, beneficiary_id, destination_account_id, amount_cents, fee_cents, status, memo, idempotency_key, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'completed\', ?, ?, UTC_TIMESTAMP())');
            $stmt->execute([$transaction['transaction_id'], $userId, $source['account_id'], $beneficiaryId, $beneficiary['destination_account_id'], $amountCents, $quote['fee_cents'], mb_substr(trim($memo), 0, 140), $idempotencyKey]);
            $financial->postEntries($transaction['transaction_id'], [
                ['account_id' => (int) $source['account_id'], 'ledger_account' => 'customer:' . $source['account_id'], 'amount_cents' => -$quote['total_cents']],
                ['account_id' => (int) $beneficiary['destination_account_id'], 'ledger_account' => 'customer:' . $beneficiary['destination_account_id'], 'amount_cents' => $amountCents],
                ['account_id' => null, 'ledger_account' => 'transfer_fee_revenue', 'amount_cents' => $quote['fee_cents']],
            ]);
            $this->pdo->prepare('UPDATE accounts SET balance_cents = balance_cents - ? WHERE account_id = ?')->execute([$quote['total_cents'], $source['account_id']]);
            $this->pdo->prepare('UPDATE accounts SET balance_cents = balance_cents + ? WHERE account_id = ?')->execute([$amountCents, $beneficiary['destination_account_id']]);
            $financial->notify($userId, 'transfer_completed', 'Transfer completed', Money::format($amountCents) . ' was sent to ' . $beneficiary['display_name'] . '.');
            $financial->notify((int) $beneficiary['destination_user_id'], 'transfer_received', 'Transfer received', Money::format($amountCents) . ' was received.');
            $this->pdo->commit();
            return $transaction;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
