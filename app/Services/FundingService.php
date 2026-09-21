<?php
declare(strict_types=1);

final class FundingService
{
    public function __construct(private PDO $pdo, private CardFundingProvider $provider) {}

    public function quote(int $amountCents): array
    {
        requireFeature($this->pdo, 'features.card_funding', 'Card funding is temporarily unavailable.');
        $cfg = config('financial')['funding'];
        if ($amountCents < $cfg['minimum_cents'] || $amountCents > $cfg['maximum_cents']) throw new InvalidArgumentException('Funding amount is outside the available limits.');
        $fee = Money::percentageFee($amountCents, $cfg['fee_basis_points'], $cfg['fee_flat_cents']);
        return ['amount_cents' => $amountCents, 'fee_cents' => $fee, 'total_cents' => $amountCents + $fee];
    }

    public function fund(int $userId, int $cardId, int $amountCents, string $idempotencyKey): array
    {
        requireFeature($this->pdo, 'features.card_funding', 'Card funding is temporarily unavailable.');
        $financial = new FinancialService($this->pdo);
        if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) return $existing;
        if (config('financial.provider_mode') !== 'sandbox') throw new RuntimeException('Live card funding is unavailable until a provider is configured.');
        $quote = $this->quote($amountCents);
        $this->pdo->beginTransaction();
        try {
            if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) { $this->pdo->commit(); return $existing; }
            $account = $financial->accountForUser($userId, true);
            $card = (new CardService($this->pdo))->ownedActiveCard($userId, $cardId, true);
            $daily = $this->pdo->prepare('SELECT COALESCE(SUM(amount_cents),0) FROM card_fundings WHERE user_id = ? AND status IN (\'processing\',\'completed\') AND initiated_at >= UTC_DATE()');
            $daily->execute([$userId]);
            if ((int) $daily->fetchColumn() + $amountCents > config('financial')['funding']['daily_limit_cents']) throw new RuntimeException('Daily funding limit exceeded.');
            $providerResult = $this->provider->authorize(['amount_cents' => $amountCents, 'token' => $card['provider_payment_method_token'], 'network' => $card['network'], 'idempotency_key' => $idempotencyKey]);
            $transaction = $financial->createTransaction($userId, (int) $account['account_id'], 'card_funding', $amountCents, $quote['fee_cents'], $providerResult['status'], 'Card funding from ' . ucfirst($card['network']) . ' ending ' . $card['last_four'], $idempotencyKey, ['provider_mode' => $providerResult['mode']]);
            $this->pdo->prepare('UPDATE financial_transactions SET provider_reference=? WHERE transaction_id=?')->execute([$providerResult['provider_reference'], $transaction['transaction_id']]);
            $stmt = $this->pdo->prepare('INSERT INTO card_fundings (financial_transaction_id, user_id, destination_account_id, card_id, provider, provider_reference, network, amount_cents, fee_cents, total_charged_cents, status, idempotency_key, provider_metadata_json, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $completedAt = $providerResult['status'] === 'completed' ? gmdate('Y-m-d H:i:s') : null;
            $stmt->execute([$transaction['transaction_id'], $userId, $account['account_id'], $cardId, $providerResult['mode'], $providerResult['provider_reference'], $card['network'], $amountCents, $quote['fee_cents'], $quote['total_cents'], $providerResult['status'], $idempotencyKey, json_encode(['message' => $providerResult['message']], JSON_THROW_ON_ERROR), $completedAt]);
            if ($providerResult['status'] === 'completed') {
                $financial->postEntries($transaction['transaction_id'], [
                    ['account_id' => (int) $account['account_id'], 'ledger_account' => 'customer:' . $account['account_id'], 'amount_cents' => $amountCents],
                    ['account_id' => null, 'ledger_account' => 'sandbox_funding_clearing', 'amount_cents' => -$quote['total_cents']],
                    ['account_id' => null, 'ledger_account' => 'funding_fee_revenue', 'amount_cents' => $quote['fee_cents']],
                ]);
                $this->pdo->prepare('UPDATE accounts SET balance_cents = balance_cents + ? WHERE account_id = ?')->execute([$amountCents, $account['account_id']]);
            }
            $financial->notify($userId, 'funding_' . $providerResult['status'], 'Card funding ' . $providerResult['status'], Money::format($amountCents) . ' funding is ' . $providerResult['status'] . ' in sandbox mode.');
            $this->pdo->commit();
            return $transaction + ['provider_message' => $providerResult['message']];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
