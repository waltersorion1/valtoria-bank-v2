<?php
declare(strict_types=1);

final class FinancialService
{
    public function __construct(private PDO $pdo) {}

    public function accountForUser(int $userId, bool $lock = false): array
    {
        $sql = 'SELECT account_id, user_id, account_number, balance_cents FROM accounts WHERE user_id = ?' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $account = $stmt->fetch();
        if (!$account) throw new RuntimeException('No account is available for this customer.');
        return $account;
    }

    public function transactionByIdempotency(int $userId, string $key): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM financial_transactions WHERE user_id = ? AND idempotency_key = ?');
        $stmt->execute([$userId, $key]);
        return $stmt->fetch() ?: null;
    }

    public function createTransaction(int $userId, int $accountId, string $type, int $amountCents, int $feeCents, string $status, string $description, string $idempotencyKey, array $metadata = []): array
    {
        $reference = Reference::generate(match ($type) { 'card_funding' => 'FND', 'card_transfer' => 'TRF', 'credit_disbursement' => 'CRD', 'credit_repayment' => 'PAY', default => 'TXN' });
        $stmt = $this->pdo->prepare('INSERT INTO financial_transactions (reference, user_id, account_id, type, amount_cents, fee_cents, net_amount_cents, currency, status, description, idempotency_key, metadata_json, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'USD\', ?, ?, ?, ?, ?)');
        $net = $type === 'card_funding' ? $amountCents : $amountCents + $feeCents;
        $completedAt = $status === 'completed' ? gmdate('Y-m-d H:i:s') : null;
        $stmt->execute([$reference, $userId, $accountId, $type, $amountCents, $feeCents, $net, $status, $description, $idempotencyKey, json_encode($metadata, JSON_THROW_ON_ERROR), $completedAt]);
        return ['transaction_id' => (int) $this->pdo->lastInsertId(), 'reference' => $reference, 'status' => $status];
    }

    public function postEntries(int $transactionId, array $entries): void
    {
        $sum = 0;
        foreach ($entries as $entry) $sum += $entry['amount_cents'];
        if ($sum !== 0) throw new LogicException('Ledger entries must balance to zero.');
        $stmt = $this->pdo->prepare('INSERT INTO ledger_entries (financial_transaction_id, account_id, ledger_account, amount_cents, currency) VALUES (?, ?, ?, ?, \'USD\')');
        foreach ($entries as $entry) {
            $stmt->execute([$transactionId, $entry['account_id'], $entry['ledger_account'], $entry['amount_cents']]);
        }
    }

    public function notify(int $userId, string $type, string $title, string $message): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $type, $title, $message]);
    }

    public function reverse(string $reference, int $actorUserId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') throw new InvalidArgumentException('A reversal reason is required.');
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM financial_transactions WHERE reference=? AND status='completed' AND reversal_of_id IS NULL FOR UPDATE");
            $stmt->execute([$reference]);
            $original = $stmt->fetch();
            if (!$original) throw new RuntimeException('Completed transaction is not available for reversal.');
            $existing = $this->pdo->prepare('SELECT * FROM financial_transactions WHERE reversal_of_id=?');
            $existing->execute([$original['transaction_id']]);
            if ($reversal = $existing->fetch()) { $this->pdo->commit(); return $reversal; }
            $entries = $this->pdo->prepare('SELECT account_id,ledger_account,amount_cents FROM ledger_entries WHERE financial_transaction_id=? ORDER BY entry_id');
            $entries->execute([$original['transaction_id']]);
            $originalEntries = $entries->fetchAll();
            if (!$originalEntries) throw new RuntimeException('Transaction has no ledger entries.');
            $accountIds = array_values(array_unique(array_map('intval', array_column(array_filter($originalEntries, fn($entry) => $entry['account_id'] !== null), 'account_id'))));
            sort($accountIds, SORT_NUMERIC);
            $balances = [];
            foreach ($accountIds as $accountId) {
                $lock = $this->pdo->prepare('SELECT balance_cents FROM accounts WHERE account_id=? FOR UPDATE');
                $lock->execute([$accountId]);
                $balances[$accountId] = (int) $lock->fetchColumn();
            }
            foreach ($originalEntries as $entry) {
                if ($entry['account_id'] === null) continue;
                $accountId = (int) $entry['account_id'];
                $newBalance = $balances[$accountId] - (int) $entry['amount_cents'];
                if ($newBalance < 0) throw new RuntimeException('Reversal would create a negative customer balance.');
                $balances[$accountId] = $newBalance;
            }
            $reversal = $this->createTransaction((int) $original['user_id'], (int) $original['account_id'], 'reversal', (int) $original['amount_cents'], 0, 'completed', 'Reversal: ' . mb_substr($reason, 0, 220), 'reversal:' . $original['transaction_id'], ['original_reference' => $reference]);
            $this->pdo->prepare('UPDATE financial_transactions SET reversal_of_id=? WHERE transaction_id=?')->execute([$original['transaction_id'], $reversal['transaction_id']]);
            $reverseEntries = [];
            foreach ($originalEntries as $entry) $reverseEntries[] = ['account_id' => $entry['account_id'] === null ? null : (int) $entry['account_id'], 'ledger_account' => $entry['ledger_account'], 'amount_cents' => -(int) $entry['amount_cents']];
            $this->postEntries($reversal['transaction_id'], $reverseEntries);
            foreach ($balances as $accountId => $balance) $this->pdo->prepare('UPDATE accounts SET balance_cents=? WHERE account_id=?')->execute([$balance, $accountId]);
            $this->pdo->prepare("UPDATE financial_transactions SET status='reversed',reversed_at=UTC_TIMESTAMP() WHERE transaction_id=?")->execute([$original['transaction_id']]);
            if ($original['type'] === 'card_transfer') $this->pdo->prepare("UPDATE transfers SET status='reversed',reversed_at=UTC_TIMESTAMP() WHERE financial_transaction_id=?")->execute([$original['transaction_id']]);
            if ($original['type'] === 'card_funding') $this->pdo->prepare("UPDATE card_fundings SET status='reversed' WHERE financial_transaction_id=?")->execute([$original['transaction_id']]);
            $this->pdo->prepare("INSERT INTO audit_logs(actor_user_id,action,target_type,target_id,metadata_json) VALUES(?,'transaction.reversed','financial_transaction',?,?)")->execute([$actorUserId, (string) $original['transaction_id'], json_encode(['reason' => $reason, 'reversal_transaction_id' => $reversal['transaction_id']], JSON_THROW_ON_ERROR)]);
            $this->notify((int) $original['user_id'], 'transaction_reversed', 'Transaction reversed', $reference . ' was reversed.');
            $this->pdo->commit();
            return $reversal;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
