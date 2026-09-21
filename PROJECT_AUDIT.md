# Valtoria Bank Repository Audit

Audit date: 2026-09-21. Scope: all first-party PHP, CSS, JavaScript, SQL, uploads, documentation, Composer metadata, and entry points in the cloned Nexus repository. Vendor source was inventoried by package rather than reviewed line by line.

## Executive summary

The application is a page-oriented PHP 8/MySQL project with working customer, administrator, OTP, transfer, deposit, withdrawal, loan, investment, reporting, and PDF-receipt features. It is reusable, but not production-safe in its inherited form. The most urgent defects were committed SMTP credentials, public account activation/deactivation links with no token validation, plaintext OTP logging/storage, development error output, inconsistent session startup, missing CSRF protection, browser-trusted upload MIME types, and destructive admin actions over GET.

Module 1 preserves the page architecture while introducing a shared configuration/security bootstrap, centralized mail transport, a Valtoria design system, public product pages, hardened authentication primitives, and a versioned foundation migration. Financial mutations remain legacy behavior and are deliberately scheduled for Module 2.

## Current architecture and entry points

- Root PHP files serve public marketing, policies, contact, registration, login, OTP, password recovery, and login-verification actions directly.
- `user/` contains authenticated customer pages and JSON chart endpoints. Financial actions are handled inside page controllers and finalized through `otp-verification.php`.
- `admin/` contains direct operations pages for users/KYC, loans, investments, messages, roles, transactions, and login records.
- `includes/` contains PDO, session, authentication, formatting, OTP, email, notification, and footer helpers. Before Module 1, responsibilities and session/error configuration were duplicated.
- `assets/` contains page-specific CSS/JS and inherited raster branding/navigation images.
- `uploads/` is web-accessible and contains committed identity, loan, and profile documents.
- Two overlapping SQL dumps (`nexusbank -Database.sql` and `sql/nexusbank (4).sql`) contain schema plus realistic personal/sample data.
- Composer dependencies are PHPMailer 6.9 and TCPDF 6.6. There is no framework, router, automated test runner, cron job, or webhook endpoint.

## Feature inventory

Public: home, services, about, blog/marketing, contact form, privacy, terms, cookie and security policies.

Authentication/onboarding: registration with ID upload, administrator approval, password hashing, login throttling stored on the user record, email OTP, emailed login approval links, password reset, logout, inactivity timeout, and login history.

Customer: dashboard, profile edit, profile image, password change, deposit, withdrawal, account-to-account transfer, transactions/filtering, PDF receipts, analytics JSON endpoints, loans and repayments, investments, and login history.

Admin: headline metrics, user approval/deletion/activation, ID review, transaction views, loan review/history, investment plans/tracking, contact-message management/replies, login records, and binary admin-role changes.

## Database overview

The legacy schema has 13 principal tables: `users`, `accounts`, `balance`, `transactions`, `login_records`, `login_verifications`, `otp_verification`, `id_verifications`, `loans`, `loan_history`, `investments`, `investment_plans`, and `contact_messages`.

Balances and transaction amounts use `DECIMAL`, not integer cents. `accounts.balance` is authoritative in practice while the `balance` table acts as an inconsistent snapshot history. Transactions lack status, fee, reference, idempotency, provider, and immutable-ledger fields. Loans may be deleted after repayment, damaging auditability. No cards, beneficiaries, funding instruments, ledger entries, notifications, support threads, permissions, audit logs, or provider events exist. The Module 1 migration adds role/KYC foundations, hashed-OTP capacity, security events, and audit logs without changing financial tables.

## Security findings

### Critical

- Real-looking Gmail usernames and app passwords were committed in multiple mail/contact files and `mail_debug.log`. They must be revoked and rotated outside Git; deleting them from the working tree does not remove Git history.
- `allow_attempt.php` and `report_suspicious.php` accepted a user ID and ignored the token, allowing unauthenticated account-state changes. Both endpoints are disabled in Module 1.
- Identity, loan-verification, and profile uploads containing apparent personal data are committed and publicly addressable. Remove them from history after confirming retention obligations and rotate any exposed test identities.
- Admin mutations can approve, delete, suspend, disburse, or change records through GET parameters with no CSRF protection. Module 1 provides the CSRF foundation; conversion of every legacy admin action remains required.

### High

- OTPs were stored and logged in plaintext. New OTPs are SHA-256 digests after migration; legacy codes are invalidated by the migration.
- Password-reset tokens were stored raw, a localhost URL was hard-coded, and account existence was disclosed. New reset tokens are stored as digests and URLs use `APP_URL`.
- Several administrator data endpoints lacked explicit admin authorization. Known transaction endpoints are now guarded; a complete endpoint matrix is still required.
- Uploads trusted client MIME/extension and lived under the executable web root. Registration now detects MIME and randomizes names; server rules block executable extensions, but storage outside the document root remains preferred.
- Production pages enabled `display_errors`, exposing paths/queries. Central bootstrap controls this through `APP_DEBUG`; remaining page-local overrides must be removed.
- Admin role is a mutable boolean and role changes are not audited. The migration introduces a transitional role field and audit table.

### Medium

- Session IDs were not consistently regenerated after successful OTP login; cookie flags and session fingerprinting were inconsistent.
- Login throttling is account-only, enabling denial of service and providing no IP/device dimension.
- Some exception messages are rendered directly, multiple outputs lack escaping, and contact/admin email HTML mixes stored input.
- Login verification links and sensitive actions place bearer tokens in URLs, which can leak through history/referrers.
- Profile upload and numerous financial/customer forms lack CSRF validation.
- Static SQL dumps include user emails, addresses, phone numbers, password hashes, and operational records.

### Financial integrity risks (deferred to Module 2)

- Money is calculated with decimal strings/floats rather than integer cents.
- Deposit is locally simulated; there is no payment provider authorization.
- Several balance updates lack complete transaction boundaries, row locks, idempotency, or replay prevention.
- Transactions have no lifecycle state or unique human-readable reference.
- Loan/investment calculations are scattered, and completed records can be deleted.
- The product stores no card token model; card funding/card-to-card transfer domains do not yet exist.

## Code-quality findings

Business logic, SQL, request handling, and HTML are mixed in large page files. Navigation and sidebar markup are duplicated. Profile update logic is duplicated in the same file. Naming and timezones are inconsistent, paths are relative and fragile, and two SQL dumps can drift. There are many page-specific stylesheets plus extensive inline CSS. There is no automated test suite or migration runner.

## UI/UX findings

The inherited interface uses inconsistent Nexus/SecureBank/TrustBank names, multiple palettes, numerous raster navigation icons, fixed desktop layouts, emoji contact details, and duplicated headers/footers. Empty/error/loading states and accessible focus behavior are inconsistent. Charts use real endpoints, which is worth retaining, but their visual loading/error handling needs refinement.

## Reuse, refactor, replace

Reuse: PDO prepared-query patterns, password hashing, transactional transfer finalization with row locks, account/login data retrieval, TCPDF receipt capability, PHPMailer dependency, real analytics queries, and the basic KYC review lifecycle.

Refactor: shared layouts/navigation, authentication orchestration, uploads, admin actions, mail/notifications, dashboard queries, transaction filters, OTP flows, loans, and reporting.

Replace/remove: committed secrets and debug logs, unauthenticated account-state endpoints, raw/legacy OTPs, fabricated licensing copy in receipts, hard-coded localhost URLs, destructive financial deletes, client-trusted upload metadata, duplicated balance model, and unrelated investment positioning after product confirmation.

## Target architecture

Retain direct PHP entry points during the staged migration. Shared bootstrapping lives in `config/` and `includes/`; reusable layouts and services can move incrementally into `app/` and `views/` when they remove duplication. Domain boundaries will be Customer/Identity, Account/Ledger, Card, Funding, Beneficiary/Transfer, Transaction, Credit/Repayment, Notification, Support, Security Event, Audit, and Provider Event. Controllers must authorize, validate, and delegate; services own monetary rules and transactions; repositories own SQL; views escape output.

## Migration strategy

1. Back up and test against a scrubbed database.
2. Apply numbered migrations once, recording them operationally until a migration runner is introduced.
3. Module 1 adds nonfinancial foundations and invalidates legacy plaintext OTPs.
4. Module 2 introduces cents-based columns/new ledger tables alongside legacy decimal columns, backfills with reconciliation checks, switches reads/writes, and only later retires old fields.
5. Preserve financial history; corrections use reversals/adjustments, never cascade deletion.

## Three-module plan

Module 1: audit, configuration, PDO/session/error foundations, CSRF/validation/roles, Valtoria public/auth/customer/admin shells, documentation, and safe asset conventions.

Module 2 (requires explicit approval): integer-cents ledger, immutable transactions, cards/token metadata, provider/sandbox funding, beneficiaries and transfers, Visa eligibility/credit/repayments, statements, notifications, and reconciliation.

Module 3 (requires explicit approval): complete operations console, granular permissions, audit coverage, support administration, security/authorization/upload review, accessibility/responsive QA, deployment readiness, and legacy cleanup.
