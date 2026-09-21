# Changelog

## 2026-09-21 — Account onboarding refinement

- Rebuilt account registration as a responsive, accessible application with clearer validation and legal consent.
- Moved date-of-birth and identity-document collection into the authenticated customer profile.
- Added protected identity upload storage, randomized filenames, MIME/size validation, review-state reset, and audited document delivery.
- Added migration 005 to support deferred identity collection without placeholder age or birth-year data.
- Split registration into two viewport-conscious steps and hide the onboarding story on mobile.
- Added configurable account approval and email OTP controls; both default to streamlined auto-approval with OTP disabled.

## 2026-09-21 — Module 3

- Completed the granular operations console for customers/KYC, cards, money movement, credit, support, settings, audit, and reconciliation.
- Added role permissions, session-version invalidation, audited state changes, internal notes, and authenticated support threads.
- Enforced stored feature toggles in financial/support services and added maintenance mode.
- Hardened contact validation/email escaping, OTP login/resend, identity-document delivery, and customer support ownership.
- Removed obsolete GET-based admin/login mutations, restored the credit operations route, and completed responsive/accessibility release checks.
- Verified all migrations on an isolated MariaDB instance, added Module 3 tests, reconciled ledger balances, and completed HTTP smoke tests.

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
