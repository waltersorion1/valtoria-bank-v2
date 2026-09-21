-- Valtoria Bank Module 2: authoritative cents, ledger, cards, funding, transfers and credit.
-- Apply once after backup and after 001_module1_foundation.sql.

ALTER TABLE accounts ADD COLUMN balance_cents BIGINT NOT NULL DEFAULT 0 AFTER balance;
UPDATE accounts SET balance_cents = ROUND(balance * 100);

CREATE TABLE financial_transactions (
    transaction_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(32) NOT NULL,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    fee_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    net_amount_cents BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    status VARCHAR(24) NOT NULL,
    description VARCHAR(255) NULL,
    provider_reference VARCHAR(100) NULL,
    idempotency_key VARCHAR(80) NOT NULL,
    metadata_json JSON NULL,
    failure_code VARCHAR(64) NULL,
    reversal_of_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    reversed_at DATETIME NULL,
    PRIMARY KEY (transaction_id),
    UNIQUE KEY uq_financial_reference (reference),
    UNIQUE KEY uq_financial_idempotency (user_id, idempotency_key),
    KEY idx_financial_account_created (account_id, created_at),
    KEY idx_financial_user_status_created (user_id, status, created_at),
    CONSTRAINT fk_financial_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_financial_account FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    CONSTRAINT fk_financial_reversal FOREIGN KEY (reversal_of_id) REFERENCES financial_transactions(transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ledger_entries (
    entry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    financial_transaction_id BIGINT UNSIGNED NULL,
    account_id INT NULL,
    ledger_account VARCHAR(100) NOT NULL,
    amount_cents BIGINT NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (entry_id),
    KEY idx_ledger_transaction (financial_transaction_id),
    KEY idx_ledger_account_created (account_id, created_at),
    CONSTRAINT fk_ledger_transaction FOREIGN KEY (financial_transaction_id) REFERENCES financial_transactions(transaction_id),
    CONSTRAINT fk_ledger_account FOREIGN KEY (account_id) REFERENCES accounts(account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ledger_entries (financial_transaction_id, account_id, ledger_account, amount_cents)
SELECT NULL, account_id, CONCAT('customer:', account_id), balance_cents FROM accounts WHERE balance_cents <> 0;
INSERT INTO ledger_entries (financial_transaction_id, account_id, ledger_account, amount_cents)
SELECT NULL, NULL, 'migration_opening_equity', -COALESCE(SUM(balance_cents),0) FROM accounts HAVING SUM(balance_cents) <> 0;

CREATE TABLE linked_cards (
    card_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider VARCHAR(40) NOT NULL,
    provider_payment_method_token VARCHAR(191) NOT NULL,
    network VARCHAR(20) NOT NULL,
    last_four CHAR(4) NOT NULL,
    cardholder_name VARCHAR(100) NOT NULL,
    expiry_month TINYINT UNSIGNED NULL,
    expiry_year SMALLINT UNSIGNED NULL,
    verification_status VARCHAR(24) NOT NULL DEFAULT 'pending',
    status VARCHAR(24) NOT NULL DEFAULT 'active',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    PRIMARY KEY (card_id),
    UNIQUE KEY uq_card_provider_token (provider, provider_payment_method_token),
    KEY idx_cards_user_status (user_id, status),
    CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE beneficiaries (
    beneficiary_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    destination_account_number VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (beneficiary_id),
    UNIQUE KEY uq_beneficiary_destination (user_id, destination_account_number),
    KEY idx_beneficiary_user_status (user_id, status),
    CONSTRAINT fk_beneficiary_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE card_fundings (
    funding_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    financial_transaction_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    destination_account_id INT NOT NULL,
    card_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(40) NOT NULL,
    provider_reference VARCHAR(100) NULL,
    network VARCHAR(20) NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    fee_cents BIGINT UNSIGNED NOT NULL,
    total_charged_cents BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL,
    idempotency_key VARCHAR(80) NOT NULL,
    provider_metadata_json JSON NULL,
    failure_code VARCHAR(64) NULL,
    initiated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    PRIMARY KEY (funding_id),
    UNIQUE KEY uq_funding_transaction (financial_transaction_id),
    UNIQUE KEY uq_funding_idempotency (user_id, idempotency_key),
    KEY idx_funding_user_created (user_id, initiated_at),
    CONSTRAINT fk_funding_transaction FOREIGN KEY (financial_transaction_id) REFERENCES financial_transactions(transaction_id),
    CONSTRAINT fk_funding_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_funding_account FOREIGN KEY (destination_account_id) REFERENCES accounts(account_id),
    CONSTRAINT fk_funding_card FOREIGN KEY (card_id) REFERENCES linked_cards(card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transfers (
    transfer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    financial_transaction_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    source_account_id INT NOT NULL,
    beneficiary_id BIGINT UNSIGNED NOT NULL,
    destination_account_id INT NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    fee_cents BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL,
    memo VARCHAR(140) NULL,
    idempotency_key VARCHAR(80) NOT NULL,
    initiated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    reversed_at DATETIME NULL,
    PRIMARY KEY (transfer_id),
    UNIQUE KEY uq_transfer_transaction (financial_transaction_id),
    UNIQUE KEY uq_transfer_idempotency (user_id, idempotency_key),
    KEY idx_transfer_user_created (user_id, initiated_at),
    CONSTRAINT fk_transfer_transaction FOREIGN KEY (financial_transaction_id) REFERENCES financial_transactions(transaction_id),
    CONSTRAINT fk_transfer_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_transfer_source FOREIGN KEY (source_account_id) REFERENCES accounts(account_id),
    CONSTRAINT fk_transfer_beneficiary FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(beneficiary_id),
    CONSTRAINT fk_transfer_destination FOREIGN KEY (destination_account_id) REFERENCES accounts(account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(64) NOT NULL,
    title VARCHAR(120) NOT NULL,
    message VARCHAR(500) NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    KEY idx_notifications_user_read (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE credit_applications (
    application_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    requested_amount_cents BIGINT UNSIGNED NOT NULL,
    approved_amount_cents BIGINT UNSIGNED NULL,
    term_months SMALLINT UNSIGNED NOT NULL,
    annual_rate_basis_points INT UNSIGNED NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'under_review',
    decision_note VARCHAR(1000) NULL,
    reviewed_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    accepted_at DATETIME NULL,
    PRIMARY KEY (application_id),
    KEY idx_credit_application_user (user_id, created_at),
    KEY idx_credit_application_status (status, created_at),
    CONSTRAINT fk_credit_application_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_credit_application_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE credit_facilities (
    facility_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    reference VARCHAR(32) NOT NULL,
    principal_cents BIGINT UNSIGNED NOT NULL,
    annual_rate_basis_points INT UNSIGNED NOT NULL,
    term_months SMALLINT UNSIGNED NOT NULL,
    total_interest_cents BIGINT UNSIGNED NOT NULL,
    outstanding_cents BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL,
    disbursed_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    PRIMARY KEY (facility_id),
    UNIQUE KEY uq_credit_facility_application (application_id),
    UNIQUE KEY uq_credit_facility_reference (reference),
    KEY idx_credit_facility_user_status (user_id, status),
    CONSTRAINT fk_credit_facility_application FOREIGN KEY (application_id) REFERENCES credit_applications(application_id),
    CONSTRAINT fk_credit_facility_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_credit_facility_account FOREIGN KEY (account_id) REFERENCES accounts(account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE repayment_schedule (
    schedule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    facility_id BIGINT UNSIGNED NOT NULL,
    installment_number SMALLINT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    amount_due_cents BIGINT UNSIGNED NOT NULL,
    amount_paid_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(24) NOT NULL DEFAULT 'scheduled',
    paid_at DATETIME NULL,
    PRIMARY KEY (schedule_id),
    UNIQUE KEY uq_schedule_installment (facility_id, installment_number),
    KEY idx_schedule_due_status (due_date, status),
    CONSTRAINT fk_schedule_facility FOREIGN KEY (facility_id) REFERENCES credit_facilities(facility_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE repayments (
    repayment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    facility_id BIGINT UNSIGNED NOT NULL,
    financial_transaction_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    status VARCHAR(24) NOT NULL,
    idempotency_key VARCHAR(80) NOT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (repayment_id),
    UNIQUE KEY uq_repayment_transaction (financial_transaction_id),
    UNIQUE KEY uq_repayment_idempotency (user_id, idempotency_key),
    CONSTRAINT fk_repayment_facility FOREIGN KEY (facility_id) REFERENCES credit_facilities(facility_id),
    CONSTRAINT fk_repayment_transaction FOREIGN KEY (financial_transaction_id) REFERENCES financial_transactions(transaction_id),
    CONSTRAINT fk_repayment_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE provider_events (
    provider_event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(40) NOT NULL,
    provider_event_reference VARCHAR(120) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (provider_event_id),
    UNIQUE KEY uq_provider_event (provider, provider_event_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_settings (
    setting_key VARCHAR(100) NOT NULL,
    value_json JSON NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key),
    CONSTRAINT fk_product_setting_actor FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$
CREATE TRIGGER accounts_balance_cents_sync_insert BEFORE INSERT ON accounts FOR EACH ROW
BEGIN
    IF NEW.balance_cents = 0 AND NEW.balance <> 0 THEN SET NEW.balance_cents = ROUND(NEW.balance * 100); END IF;
    SET NEW.balance = NEW.balance_cents / 100;
END$$
CREATE TRIGGER accounts_balance_cents_sync_update BEFORE UPDATE ON accounts FOR EACH ROW
BEGIN
    IF NEW.balance <> OLD.balance AND NEW.balance_cents = OLD.balance_cents THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Legacy decimal balance mutation is disabled';
    END IF;
    SET NEW.balance = NEW.balance_cents / 100;
END$$
DELIMITER ;
