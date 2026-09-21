# Database

The inherited MySQL/MariaDB database is named `nexusbank` by default and is configurable through `.env`. PDO uses UTF-8, exceptions, associative fetches, and native prepared statements.

The original dumps are retained for legacy reference but contain sample/personal data and must not be used as production seeds. Use a scrubbed copy for development.

## Migrations

Apply `database/migrations/001_module1_foundation.sql` once after backup. It expands OTP storage for digests, invalidates old plaintext OTPs, adds transitional role/KYC/session-version fields, and creates security/audit event tables. It does not convert money or change financial behavior; cents/ledger migration belongs to Module 2.

Example (adjust executable path and credentials):

```sh
mysql -u root -p nexusbank < database/migrations/001_module1_foundation.sql
```

Current authoritative legacy balance is `accounts.balance`; `balance` is an inconsistent historical snapshot table. Do not build new features on it.

## Module 2 migration

Apply `database/migrations/002_module2_financial_core.sql` after migration 001. It adds `accounts.balance_cents` and backfills it from the legacy decimal balance, then creates balanced opening ledger entries. From that point, `balance_cents` and the ledger are authoritative; triggers reject direct legacy decimal balance updates and mirror cents back to the compatibility column.

New tables are `financial_transactions`, `ledger_entries`, `linked_cards`, `beneficiaries`, `card_fundings`, `transfers`, `notifications`, `credit_applications`, `credit_facilities`, `repayment_schedule`, `repayments`, `provider_events`, and `product_settings`. Financial history has restrictive foreign keys and no cascade deletion. Migration `003_module2_legacy_transaction_history.sql` copies inherited transactions into the new transaction center as `LEG-*` pre-ledger records. It deliberately does not repost them: the balanced opening entries from migration 002 form the authoritative reconciliation boundary.

Reconciliation queries:

```sql
SELECT COALESCE(SUM(amount_cents), 0) FROM ledger_entries;
SELECT a.account_id FROM accounts a
WHERE a.balance_cents <> COALESCE((SELECT SUM(amount_cents) FROM ledger_entries l WHERE l.account_id=a.account_id), 0);
```

The first result and the second query's row count must both be zero.

## Module 3 migration

Apply `004_module3_operations.sql` after migration 003. It adds authenticated support cases and messages, internal customer notes, operations indexes, and fail-closed feature-toggle records. The migration was verified from the original schema through all four migrations on MariaDB 10.4.32; 31 application tables were present and both reconciliation checks returned zero.

## Profile identity migration

Apply `005_profile_identity_onboarding.sql` after migration 004. It makes the inherited `age` and `birth_year` fields nullable, adds an authoritative nullable `date_of_birth`, and enforces one identity-verification record per customer. New registrations intentionally leave identity fields empty; customers submit them later from the authenticated profile.

Apply `006_onboarding_controls.sql` after migration 005. It adds disabled-by-default controls for manual account approval and email OTP. With approval disabled, registration creates an approved customer account and signs the customer in. Email OTP is used only when both its database switch and complete SMTP environment configuration are enabled.
