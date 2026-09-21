# Security

## Implemented in Module 1

- Environment-based database/mail/application configuration; `.env` is ignored.
- Native PDO prepares with exception mode.
- Secure/HttpOnly/SameSite session-cookie baseline and idle timeout.
- Shared contextual HTML escaping and CSRF primitives.
- Centralized production-safe exception handling.
- SMTP secrets removed from application files and mail centralized.
- New OTP and password-reset secrets stored as SHA-256 digests.
- MIME-detected, randomized registration document names.
- Web-server denial rules for executable upload extensions and directory listing.
- Unauthenticated activation/deactivation endpoints disabled.
- Explicit admin guards added to transaction data endpoints.
- Transitional role, KYC, security-event, and audit-log schema.

## Required operational actions

Immediately revoke every SMTP app password previously committed. Purge secrets and personal uploads from Git history using a coordinated history rewrite; all collaborators must then re-clone. Keep uploads outside the public document root in production. Set `APP_DEBUG=false`, use HTTPS, use a least-privilege DB account, restrict filesystem permissions, and protect backups.

## Remaining risk

Legacy nonfinancial state-changing forms still need systematic CSRF conversion; admin GET mutations must become POST; object-level authorization needs a full matrix; uploads need centralized storage; and login throttling needs IP/device dimensions. No compliance, regulatory, card-network endorsement, or absolute-security claim is made.

## Module 2 controls

- Authoritative amounts use integer USD cents and strict decimal parsing.
- Monetary services use transactions, deterministic row locking, server-side fees/limits, unique idempotency keys, and balanced ledger assertions.
- Card profiles store sandbox/provider tokens, network, last four, label, and permitted expiry only; forms never request PAN or CVV.
- Transaction ownership protects customer details and receipts.
- Beneficiary ownership and destination validity are checked server-side.
- Visa credit eligibility uses only KYC, verified Visa metadata, account age, and completed transaction history.
- Reversals preserve original records and post inverse ledger entries.
- The sandbox funding adapter is visibly identified and cannot be mistaken for a live external authorization.

Step-up transfer verification is fail-closed when `TRANSFER_STEP_UP_CENTS` is configured above zero; a real OTP/provider step-up adapter is still required before those transfers can proceed.

## Module 3 review

- Operations endpoints require granular server-side permissions; read-only auditors cannot mutate records.
- Every implemented admin mutation uses POST, CSRF validation, allow-listed state values, prepared SQL, and audit logging.
- Session-version checks invalidate restricted customers and administrators whose role changes.
- The obsolete email-link login approval endpoints were removed; OTP resend is POST+CSRF.
- Feature toggles fail closed in the relevant financial/support services, and maintenance mode blocks customer application access.
- Customer support queries enforce ownership and exclude internal notes.
- Identity-document delivery verifies permission, real-path containment, file existence, MIME allow-list, and `nosniff` headers.
- Contact data is stored raw, escaped only for HTML output/email, length-validated, rate-limited per session, and returns delivery-neutral messaging.

Remaining launch requirements include external penetration testing, moving all uploads outside the document root, coordinated Git-history scrubbing, infrastructure rate limiting, CSP/security headers at the web server, and real provider/compliance review.
