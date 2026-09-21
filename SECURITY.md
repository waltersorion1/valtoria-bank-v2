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

Legacy state-changing forms still need systematic CSRF conversion; admin GET mutations must become POST; object-level authorization needs a full matrix; uploads need centralized storage; login throttling needs IP/device dimensions; and financial flows require the Module 2 ledger/idempotency work. No compliance, regulatory, card-network endorsement, or absolute-security claim is made.
