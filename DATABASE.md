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
