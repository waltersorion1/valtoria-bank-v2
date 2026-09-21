-- Preserve inherited transaction history in the Valtoria transaction center.
-- These records predate the authoritative ledger. Opening-balance entries in migration 002
-- establish the reconciliation boundary, so historical rows are not posted again.

INSERT IGNORE INTO financial_transactions
    (reference, user_id, account_id, type, amount_cents, fee_cents, net_amount_cents, currency, status, description, idempotency_key, metadata_json, created_at, completed_at)
SELECT
    CONCAT('LEG-', LPAD(t.transaction_id, 10, '0')),
    a.user_id,
    t.account_id,
    CASE t.type
        WHEN 'deposit' THEN 'legacy_deposit'
        WHEN 'withdrawal' THEN 'legacy_withdrawal'
        WHEN 'transfer_in' THEN 'legacy_transfer_in'
        WHEN 'transfer_out' THEN 'legacy_transfer_out'
        WHEN 'loanpayment' THEN 'legacy_credit_repayment'
        WHEN 'approved_loan' THEN 'legacy_credit_disbursement'
        ELSE CONCAT('legacy_', LEFT(REPLACE(LOWER(t.type), ' ', '_'), 30))
    END,
    ABS(ROUND(t.amount * 100)),
    0,
    ABS(ROUND(t.amount * 100)),
    'USD',
    'completed',
    t.description,
    CONCAT('legacy-transaction-', t.transaction_id),
    JSON_OBJECT('legacy_transaction_id', t.transaction_id, 'pre_ledger', TRUE),
    t.created_at,
    t.created_at
FROM transactions t
JOIN accounts a ON a.account_id = t.account_id;
