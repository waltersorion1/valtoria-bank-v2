-- Valtoria Bank: move birth and identity collection from registration to the authenticated profile.
-- Apply once after 004_module3_operations.sql.

ALTER TABLE users
    MODIFY age INT NULL,
    MODIFY birth_year INT NULL,
    ADD COLUMN date_of_birth DATE NULL AFTER birth_year;

ALTER TABLE id_verifications
    ADD UNIQUE KEY uq_id_verifications_user (user_id);
