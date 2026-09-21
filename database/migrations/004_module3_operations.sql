-- Valtoria Bank Module 3: operations, support and launch-readiness controls.
-- Apply once after 003_module2_legacy_transaction_history.sql.

CREATE TABLE support_cases (
    case_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(24) NOT NULL,
    user_id INT NOT NULL,
    category VARCHAR(40) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    priority VARCHAR(16) NOT NULL DEFAULT 'normal',
    status VARCHAR(24) NOT NULL DEFAULT 'open',
    assigned_to INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    PRIMARY KEY (case_id),
    UNIQUE KEY uq_support_reference (reference),
    KEY idx_support_user_created (user_id, created_at),
    KEY idx_support_queue (status, priority, updated_at),
    CONSTRAINT fk_support_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_support_assignee FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE support_messages (
    message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_id BIGINT UNSIGNED NOT NULL,
    author_user_id INT NOT NULL,
    body TEXT NOT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_support_messages_case (case_id, created_at),
    CONSTRAINT fk_support_message_case FOREIGN KEY (case_id) REFERENCES support_cases(case_id),
    CONSTRAINT fk_support_message_author FOREIGN KEY (author_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin_customer_notes (
    note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_user_id INT NOT NULL,
    actor_user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (note_id),
    KEY idx_admin_notes_customer (customer_user_id, created_at),
    CONSTRAINT fk_admin_note_customer FOREIGN KEY (customer_user_id) REFERENCES users(user_id),
    CONSTRAINT fk_admin_note_actor FOREIGN KEY (actor_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO product_settings (setting_key, value_json, updated_by) VALUES
('features.card_funding', 'true', NULL),
('features.transfers', 'true', NULL),
('features.credit_applications', 'true', NULL),
('features.support_cases', 'true', NULL),
('system.maintenance_mode', 'false', NULL)
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

CREATE INDEX idx_financial_status_type_created ON financial_transactions(status, type, created_at);
CREATE INDEX idx_cards_status_network ON linked_cards(status, network);
CREATE INDEX idx_users_role_status ON users(role, status, is_active);
