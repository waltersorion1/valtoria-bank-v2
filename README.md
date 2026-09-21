# Valtoria Bank

Valtoria Bank is a staged modernization of the cloned Nexus PHP/MySQL project into a card-focused financial platform. Module 1 establishes the secure configuration, authentication, documentation, visual system, and public/customer/admin foundations. Legacy financial behavior remains present while it is prepared for the cents-based ledger refactor in Module 2.

This repository does not claim a banking charter, deposit insurance, regulatory approval, PCI compliance, or endorsement by Visa or Mastercard.

## Requirements

- PHP 8.0+ with PDO MySQL, Fileinfo, OpenSSL, and Mbstring
- MySQL 8+ or a compatible MariaDB release
- Apache (XAMPP is supported for local development)
- Composer dependencies installed (`PHPMailer` and `TCPDF`)

## Local installation

1. Clone the repository into the web root.
2. Copy `.env.example` to `.env` and set local database/application values.
3. Import a scrubbed legacy schema if starting from an empty database.
4. Back up the database and apply `database/migrations/001_module1_foundation.sql`.
5. Run `composer install` when `vendor/` is absent.
6. Point Apache at the project and visit the `APP_URL` value.

Do not use the committed legacy SQL sample data in a public environment. It contains realistic personal records.

## Environment

The main settings are `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`, `SESSION_NAME`, `SESSION_IDLE_TIMEOUT`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

Keep `APP_DEBUG=false` outside local development. Use a least-privilege database account rather than `root` in shared or production environments.

## Email

Email is disabled by default. Configure `MAIL_ENABLED`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, and `SUPPORT_EMAIL` in `.env`. Never commit credentials. Previously committed SMTP credentials must be revoked and removed from repository history.

## Database and migrations

See [DATABASE.md](DATABASE.md). Migrations are forward-only SQL files and should be applied once after a verified backup. Module 1 intentionally does not alter financial amount representation.

## Provider and sandbox configuration

No live card payment, transfer rail, KYC provider, or credit bureau is configured. Existing deposits are legacy simulated behavior and must not be represented as real external charges. Provider interfaces and sandbox adapters are Module 2 work after explicit approval.

## Assets

See [ASSETS.md](ASSETS.md) for exact target paths, formats, dimensions, variants, and fallbacks. The new public shell uses a CSS wordmark until final Valtoria assets are supplied.

## Admin setup

The legacy `is_admin` field is retained for compatibility. The Module 1 migration adds a transitional `role` field and maps current administrators to `super_admin`. Do not grant roles by editing query parameters or exposing database tools publicly.

## Testing

There is no inherited automated test framework. Before each deployment:

```powershell
Get-ChildItem -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch '\\vendor\\' } | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
C:\xampp\php\php.exe -r "require 'includes/bootstrap.php'; echo config('app.name');"
```

Also manually verify registration, login/OTP, logout, password reset, customer/admin authorization, CSRF rejection, uploads, and core page rendering against a disposable database.

## Deployment notes

- Serve over HTTPS and set `APP_DEBUG=false`.
- Keep `.env`, logs, database dumps, and customer uploads out of the public document root.
- Remove sample/personal data before deployment.
- Configure web-server upload execution denial (the included `.htaccess` applies to Apache).
- Rotate exposed historical credentials and rewrite Git history in coordination with collaborators.
- Back up and test migrations before applying them.

Further detail is in [PROJECT_AUDIT.md](PROJECT_AUDIT.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [SECURITY.md](SECURITY.md).
