-- Valtoria Bank: configurable onboarding approval and email OTP controls.
-- Apply once after 005_profile_identity_onboarding.sql.

INSERT INTO product_settings(setting_key,value_json,updated_by) VALUES
('features.require_account_approval','false',NULL),
('features.email_otp','false',NULL)
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
