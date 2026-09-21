<?php
declare(strict_types=1);

final class CreditService
{
    public function __construct(private PDO $pdo) {}

    public function eligibility(int $userId): array
    {
        $cfg = config('financial')['credit'];
        $stmt = $this->pdo->prepare('SELECT u.kyc_status, DATEDIFF(UTC_DATE(), DATE(u.created_at)) account_age_days, EXISTS(SELECT 1 FROM linked_cards c WHERE c.user_id=u.user_id AND c.network=\'visa\' AND c.status=\'active\' AND c.verification_status=\'verified\') has_visa, (SELECT COUNT(*) FROM financial_transactions t WHERE t.user_id=u.user_id AND t.status=\'completed\') completed_transactions FROM users u WHERE u.user_id=?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) throw new RuntimeException('Customer not found.');
        $reasons = [];
        if ($row['kyc_status'] !== 'verified') $reasons[] = 'Identity verification must be complete.';
        if (!(bool) $row['has_visa']) $reasons[] = 'A verified Visa card is required.';
        if ((int) $row['account_age_days'] < $cfg['minimum_account_age_days']) $reasons[] = 'The account must be at least ' . $cfg['minimum_account_age_days'] . ' days old.';
        if ((int) $row['completed_transactions'] < $cfg['minimum_completed_transactions']) $reasons[] = 'At least ' . $cfg['minimum_completed_transactions'] . ' completed Valtoria transactions are required.';
        return ['eligible' => $reasons === [], 'reasons' => $reasons, 'maximum_cents' => $cfg['maximum_cents'], 'annual_rate_basis_points' => $cfg['annual_rate_basis_points'], 'allowed_terms' => $cfg['allowed_terms']];
    }

    public function apply(int $userId, int $requestedCents, int $termMonths, string $purpose): int
    {
        requireFeature($this->pdo, 'features.credit_applications', 'Credit applications are temporarily unavailable.');
        $eligibility = $this->eligibility($userId);
        $cfg = config('financial')['credit'];
        if (!$eligibility['eligible']) throw new RuntimeException('Credit eligibility requirements are not currently met.');
        if ($requestedCents < $cfg['minimum_cents'] || $requestedCents > $cfg['maximum_cents']) throw new InvalidArgumentException('Requested amount is outside the available range.');
        if (!in_array($termMonths, $cfg['allowed_terms'], true)) throw new InvalidArgumentException('Choose an available repayment term.');
        $purpose = trim($purpose);
        if ($purpose === '' || mb_strlen($purpose) > 255) throw new InvalidArgumentException('Enter a brief credit purpose.');
        $stmt = $this->pdo->prepare('INSERT INTO credit_applications (user_id, requested_amount_cents, term_months, annual_rate_basis_points, purpose, status) VALUES (?, ?, ?, ?, ?, \'under_review\')');
        $stmt->execute([$userId, $requestedCents, $termMonths, $cfg['annual_rate_basis_points'], $purpose]);
        $applicationId = (int) $this->pdo->lastInsertId();
        (new FinancialService($this->pdo))->notify($userId, 'credit_application_submitted', 'Credit application submitted', 'Your application is under review.');
        return $applicationId;
    }

    public function decide(int $applicationId, int $actorId, string $decision, ?int $approvedCents, string $note): void
    {
        if (!in_array($decision, ['approved', 'rejected', 'needs_information'], true)) throw new InvalidArgumentException('Invalid decision.');
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM credit_applications WHERE application_id=? AND status=\'under_review\' FOR UPDATE');
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application) throw new RuntimeException('Application is no longer awaiting a decision.');
            if ($decision === 'approved') {
                $approvedCents ??= (int) $application['requested_amount_cents'];
                if ($approvedCents < config('financial')['credit']['minimum_cents'] || $approvedCents > (int) $application['requested_amount_cents']) throw new InvalidArgumentException('Approved amount is invalid.');
            } else { $approvedCents = null; }
            $update = $this->pdo->prepare('UPDATE credit_applications SET status=?, approved_amount_cents=?, decision_note=?, reviewed_by=?, reviewed_at=UTC_TIMESTAMP() WHERE application_id=?');
            $update->execute([$decision, $approvedCents, mb_substr(trim($note), 0, 1000), $actorId, $applicationId]);
            $this->pdo->prepare('INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, metadata_json) VALUES (?, \'credit.decision\', \'credit_application\', ?, ?)')->execute([$actorId, (string) $applicationId, json_encode(['decision' => $decision, 'approved_amount_cents' => $approvedCents], JSON_THROW_ON_ERROR)]);
            (new FinancialService($this->pdo))->notify((int) $application['user_id'], 'credit_application_' . $decision, 'Credit application updated', 'Your application status is now ' . str_replace('_', ' ', $decision) . '.');
            $this->pdo->commit();
        } catch (Throwable $exception) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $exception; }
    }

    public function acceptAndDisburse(int $userId, int $applicationId, string $idempotencyKey): array
    {
        $financial = new FinancialService($this->pdo);
        if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) return $existing;
        $this->pdo->beginTransaction();
        try {
            if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) { $this->pdo->commit(); return $existing; }
            $stmt = $this->pdo->prepare('SELECT * FROM credit_applications WHERE application_id=? AND user_id=? AND status=\'approved\' FOR UPDATE');
            $stmt->execute([$applicationId, $userId]);
            $app = $stmt->fetch();
            if (!$app) throw new RuntimeException('This application is not available for acceptance.');
            $account = $financial->accountForUser($userId, true);
            $principal = (int) $app['approved_amount_cents'];
            $interest = intdiv(($principal * (int) $app['annual_rate_basis_points'] * (int) $app['term_months']) + 119999, 120000);
            $total = $principal + $interest;
            $transaction = $financial->createTransaction($userId, (int) $account['account_id'], 'credit_disbursement', $principal, 0, 'completed', 'Credit disbursement', $idempotencyKey, ['application_id' => $applicationId]);
            $facilityRef = Reference::generate('FAC');
            $insert = $this->pdo->prepare('INSERT INTO credit_facilities (application_id, user_id, account_id, reference, principal_cents, annual_rate_basis_points, term_months, total_interest_cents, outstanding_cents, status, disbursed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\', UTC_TIMESTAMP())');
            $insert->execute([$applicationId, $userId, $account['account_id'], $facilityRef, $principal, $app['annual_rate_basis_points'], $app['term_months'], $interest, $total]);
            $facilityId = (int) $this->pdo->lastInsertId();
            $base = intdiv($total, (int) $app['term_months']);
            $remainder = $total % (int) $app['term_months'];
            $schedule = $this->pdo->prepare('INSERT INTO repayment_schedule (facility_id, installment_number, due_date, amount_due_cents, status) VALUES (?, ?, DATE_ADD(UTC_DATE(), INTERVAL ? MONTH), ?, \'scheduled\')');
            for ($i=1; $i <= (int) $app['term_months']; $i++) $schedule->execute([$facilityId, $i, $i, $base + ($i <= $remainder ? 1 : 0)]);
            $financial->postEntries($transaction['transaction_id'], [
                ['account_id' => (int) $account['account_id'], 'ledger_account' => 'customer:' . $account['account_id'], 'amount_cents' => $principal],
                ['account_id' => null, 'ledger_account' => 'credit_receivable:' . $facilityId, 'amount_cents' => -$total],
                ['account_id' => null, 'ledger_account' => 'deferred_interest_revenue:' . $facilityId, 'amount_cents' => $interest],
            ]);
            $this->pdo->prepare('UPDATE accounts SET balance_cents=balance_cents+? WHERE account_id=?')->execute([$principal, $account['account_id']]);
            $this->pdo->prepare('UPDATE credit_applications SET status=\'accepted\', accepted_at=UTC_TIMESTAMP() WHERE application_id=?')->execute([$applicationId]);
            $financial->notify($userId, 'credit_disbursed', 'Credit disbursed', Money::format($principal) . ' was added to your Valtoria account.');
            $this->pdo->commit();
            return $transaction + ['facility_id' => $facilityId];
        } catch (Throwable $exception) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $exception; }
    }

    public function repay(int $userId, int $facilityId, int $amountCents, string $idempotencyKey): array
    {
        $financial = new FinancialService($this->pdo);
        if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) return $existing;
        if ($amountCents <= 0) throw new InvalidArgumentException('Payment must be greater than zero.');
        $this->pdo->beginTransaction();
        try {
            if ($existing = $financial->transactionByIdempotency($userId, $idempotencyKey)) { $this->pdo->commit(); return $existing; }
            $stmt = $this->pdo->prepare('SELECT * FROM credit_facilities WHERE facility_id=? AND user_id=? AND status=\'active\' FOR UPDATE');
            $stmt->execute([$facilityId, $userId]);
            $facility = $stmt->fetch();
            if (!$facility) throw new RuntimeException('Active credit facility not found.');
            $amountCents = min($amountCents, (int) $facility['outstanding_cents']);
            $account = $financial->accountForUser($userId, true);
            if ((int) $account['balance_cents'] < $amountCents) throw new RuntimeException('Insufficient available balance.');
            $transaction = $financial->createTransaction($userId, (int) $account['account_id'], 'credit_repayment', $amountCents, 0, 'completed', 'Credit repayment', $idempotencyKey, ['facility_id' => $facilityId]);
            $this->pdo->prepare('INSERT INTO repayments (facility_id, financial_transaction_id, user_id, amount_cents, status, idempotency_key, paid_at) VALUES (?, ?, ?, ?, \'completed\', ?, UTC_TIMESTAMP())')->execute([$facilityId, $transaction['transaction_id'], $userId, $amountCents, $idempotencyKey]);
            $financial->postEntries($transaction['transaction_id'], [
                ['account_id' => (int) $account['account_id'], 'ledger_account' => 'customer:' . $account['account_id'], 'amount_cents' => -$amountCents],
                ['account_id' => null, 'ledger_account' => 'credit_receivable:' . $facilityId, 'amount_cents' => $amountCents],
            ]);
            $remaining = (int) $facility['outstanding_cents'] - $amountCents;
            $this->pdo->prepare('UPDATE accounts SET balance_cents=balance_cents-? WHERE account_id=?')->execute([$amountCents, $account['account_id']]);
            $this->pdo->prepare('UPDATE credit_facilities SET outstanding_cents=?, status=? WHERE facility_id=?')->execute([$remaining, $remaining === 0 ? 'repaid' : 'active', $facilityId]);
            $this->allocateSchedulePayment($facilityId, $amountCents);
            $financial->notify($userId, 'repayment_received', 'Repayment received', Money::format($amountCents) . ' was applied to your credit balance.');
            $this->pdo->commit();
            return $transaction;
        } catch (Throwable $exception) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $exception; }
    }

    private function allocateSchedulePayment(int $facilityId, int $amountCents): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM repayment_schedule WHERE facility_id=? AND status <> \'paid\' ORDER BY installment_number FOR UPDATE');
        $stmt->execute([$facilityId]);
        foreach ($stmt->fetchAll() as $row) {
            if ($amountCents <= 0) break;
            $remaining = (int) $row['amount_due_cents'] - (int) $row['amount_paid_cents'];
            $applied = min($remaining, $amountCents);
            $newPaid = (int) $row['amount_paid_cents'] + $applied;
            $this->pdo->prepare('UPDATE repayment_schedule SET amount_paid_cents=?, status=?, paid_at=? WHERE schedule_id=?')->execute([$newPaid, $newPaid === (int) $row['amount_due_cents'] ? 'paid' : 'partial', $newPaid === (int) $row['amount_due_cents'] ? gmdate('Y-m-d H:i:s') : null, $row['schedule_id']]);
            $amountCents -= $applied;
        }
    }
}
