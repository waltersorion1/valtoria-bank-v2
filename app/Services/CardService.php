<?php
declare(strict_types=1);

final class CardService
{
    public function __construct(private PDO $pdo) {}

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM linked_cards WHERE user_id = ? AND status <> \'removed\' ORDER BY is_default DESC, created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function submitForReview(int $userId, string $network, string $lastFour, string $cardholderName, int $expiryMonth, int $expiryYear, string $partnerReference): int
    {
        $network = strtolower($network);
        if (!in_array($network, ['visa', 'mastercard'], true)) throw new InvalidArgumentException('Only Visa and Mastercard are supported.');
        if (!preg_match('/^\d{4}$/', $lastFour)) throw new InvalidArgumentException('Enter exactly the final four card digits.');
        if ($expiryMonth < 1 || $expiryMonth > 12 || $expiryYear < (int) gmdate('Y') || $expiryYear > (int) gmdate('Y') + 20) throw new InvalidArgumentException('Enter a valid card expiry date.');
        $cardholderName = trim($cardholderName);
        if ($cardholderName === '' || mb_strlen($cardholderName) > 100) throw new InvalidArgumentException('Enter the cardholder name.');

        $partnerReference = trim($partnerReference);
        if ($partnerReference !== '' && !preg_match('/^[A-Za-z0-9._-]{4,80}$/', $partnerReference)) throw new InvalidArgumentException('Enter a valid partner card reference.');
        $providerToken = 'manual_ref_' . ($partnerReference !== '' ? $partnerReference : bin2hex(random_bytes(16)));
        $this->pdo->beginTransaction();
        try {
            $hasDefault = $this->pdo->prepare('SELECT 1 FROM linked_cards WHERE user_id = ? AND is_default = 1 LIMIT 1');
            $hasDefault->execute([$userId]);
            $stmt = $this->pdo->prepare('INSERT INTO linked_cards (user_id, provider, provider_payment_method_token, network, last_four, cardholder_name, expiry_month, expiry_year, verification_status, status, is_default, verified_at) VALUES (?, \'manual_partner_review\', ?, ?, ?, ?, ?, ?, \'pending\', \'active\', 0, NULL)');
            $stmt->execute([$userId, $providerToken, $network, $lastFour, $cardholderName, $expiryMonth, $expiryYear]);
            $cardId = (int) $this->pdo->lastInsertId();
            (new FinancialService($this->pdo))->notify($userId, 'card_review_pending', 'Card submitted for review', ucfirst($network) . ' ending ' . $lastFour . ' is awaiting compatibility review.');
            $this->pdo->commit();
            return $cardId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function ownedActiveCard(int $userId, int $cardId, bool $lock = false): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM linked_cards WHERE card_id = ? AND user_id = ? AND status = \'active\' AND verification_status = \'verified\'' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute([$cardId, $userId]);
        $card = $stmt->fetch();
        if (!$card) throw new RuntimeException('The selected card is not available.');
        return $card;
    }

    public function remove(int $userId, int $cardId): void
    {
        $stmt = $this->pdo->prepare('UPDATE linked_cards SET status = \'removed\', is_default = 0 WHERE card_id = ? AND user_id = ? AND status <> \'removed\'');
        $stmt->execute([$cardId, $userId]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Card not found.');
    }
}
