# Architecture

Valtoria Bank currently uses staged modernization: direct PHP entry points remain runnable while shared concerns move behind a bootstrap and domain-oriented boundaries.

## Runtime layers

- `config/`: environment-derived application, database, and mail configuration.
- `includes/bootstrap.php`: environment loading, timezone, session-cookie policy, error behavior, and exception handling.
- `includes/security.php`: escaping, CSRF, session regeneration, safe redirects, and request helpers.
- `includes/db.php`: the single PDO connection factory/configuration point.
- `includes/public_header.php` and `public_footer.php`: responsive public shell.
- `assets/css/valtoria.css`: canonical design tokens and public components.
- `database/migrations/`: forward-only versioned schema changes.
- Existing root, `user/`, and `admin/` entry points remain transitional controllers/views.

## Direction

Future refactors should create services/repositories only when moving real duplicated behavior. Financial commands will use integer cents, database transactions, row locks, idempotency keys, immutable transaction records, and balanced ledger entries. Provider-specific behavior belongs behind adapters. Views never decide authorization or authoritative financial values.

Roles begin with `customer`, `super_admin`, `operations`, `kyc_reviewer`, `credit_officer`, `support_agent`, and `read_only_auditor`; the legacy `is_admin` flag remains temporarily for compatibility.

## Module 2 financial architecture

`app/Services` now owns card metadata, funding, transfers, ledger posting, credit decisions, disbursement, schedules, and repayments. `Money` converts decimal input to integer cents without floating-point arithmetic. `Reference` creates non-sequential customer references. Each completed monetary command creates one immutable `financial_transactions` record and zero-sum `ledger_entries`; cached `accounts.balance_cents` is updated in the same locked database transaction and is reconciled against the customer ledger.

Funding uses `CardFundingProvider`; only the explicitly labeled sandbox adapter exists. Linked cards contain tokens and safe display metadata, never PAN or CVV. Idempotency is enforced both in services and with unique database constraints. Corrections create reversal transactions and inverse ledger entries.
