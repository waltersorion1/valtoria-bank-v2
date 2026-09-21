-- Valtoria Bank Module 1 foundation migration
-- Apply once after backing up the database. Compatible with MySQL 8+/MariaDB 10.5+.

ALTER TABLE otp_verification MODIFY otp CHAR(64) NOT NULL;
UPDATE otp_verification SET is_used = 1 WHERE LENGTH(otp) <> 64;

ALTER TABLE users
    ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'customer' AFTER is_admin,
    ADD COLUMN kyc_status VARCHAR(32) NOT NULL DEFAULT 'not_started' AFTER status,
    ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER blocked_until;

UPDATE users SET role = CASE WHEN is_admin = 1 THEN 'super_admin' ELSE 'customer' END;
UPDATE users u LEFT JOIN id_verifications i ON i.user_id = u.user_id
SET u.kyc_status = CASE
    WHEN i.verification_status = 'verified' THEN 'verified'
    WHEN i.verification_status = 'rejected' THEN 'rejected'
    WHEN i.verification_id IS NOT NULL THEN 'pending'
    ELSE 'not_started'
END;

CREATE TABLE IF NOT EXISTS security_events (
    event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    event_type VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id),
    KEY idx_security_events_user_created (user_id, created_at),
    CONSTRAINT fk_security_events_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS audit_logs (
    audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(64) NULL,
    target_id VARCHAR(64) NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_audit_actor_created (actor_user_id, created_at),
    KEY idx_audit_target (target_type, target_id),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
