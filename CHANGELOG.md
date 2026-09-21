# Changelog

## 2026-09-21 — Module 2

- Added authoritative integer-cent account balances, immutable financial transactions, balanced ledger entries, references, and idempotency constraints.
- Added token-only Visa/Mastercard card profiles and a clearly labeled sandbox funding provider.
- Added beneficiaries, quoted/confirmed card-to-card transfers, server-side fees/limits, notifications, receipts, CSV statements, analytics, and reversals.
- Added deterministic Visa-card credit eligibility, applications, operations review, facilities, schedules, disbursement, and repayments.
- Retired legacy deposit, withdrawal, investment, and loan mutation paths from the customer experience.
- Added financial integration and reconciliation tests plus provider documentation.

## 2026-09-21 — Module 1

- Added repository audit and architecture/database/security/asset documentation.
- Added environment-driven configuration and hardened shared bootstrap.
- Centralized email transport and removed committed SMTP credentials from live PHP files.
- Added CSRF, escaping, session, role, and error-handling foundations.
- Hashed new OTP/password-reset secrets and hardened registration uploads.
- Disabled unauthenticated account activation/deactivation endpoints.
- Added Valtoria design tokens, public shell, home page, and core product-information pages.
- Added the first versioned foundation migration.
- Removed committed SMTP debug output and customer-upload files from repository tracking; local files are preserved and ignored.
