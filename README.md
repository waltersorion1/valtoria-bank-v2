# Valtoria Bank

Valtoria Bank is a modernized PHP/MySQL card-focused financial platform. Modules 1–3 provide the application foundation, cents-based ledger, masked card profiles, manually reviewed funding and transfers, Visa-eligible credit, customer support, and a role-protected operations console.

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
4. Back up the database and apply migrations `001_module1_foundation.sql` through `006_onboarding_controls.sql` in numeric order.
5. Run `composer install` when `vendor/` is absent.
6. Point Apache at the project and visit the `APP_URL` value.

Do not use the committed legacy SQL sample data in a public environment. It contains realistic personal records.

### Development seed

After applying migrations 001–006, replace all data in the configured development database with synthetic fixtures:

```powershell
C:\xampp\php\php.exe database\seeds\development.php --force
```

The command is intentionally destructive and requires `--force`. It creates `example.test` customers and operations roles, masked development card records, balanced manually reviewed funding/transfer activity, a credit application, notifications, and a support thread. The shared local password printed by the command must never be used outside development.

## Environment

The main settings are `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`, `SESSION_NAME`, `SESSION_IDLE_TIMEOUT`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `FINANCIAL_PROVIDER_MODE`, `SANDBOX_AUTO_COMPLETE`, and `TRANSFER_STEP_UP_CENTS`.

Keep `APP_DEBUG=false` outside local development. Use a least-privilege database account rather than `root` in shared or production environments.

## Email

Email is disabled by default. Configure `MAIL_ENABLED`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, and `SUPPORT_EMAIL` in `.env`. Never commit credentials. Previously committed SMTP credentials must be revoked and removed from repository history.

## Database and migrations

See [DATABASE.md](DATABASE.md). Migrations are forward-only SQL files and should be applied once after a verified backup. Module 1 intentionally does not alter financial amount representation.

## Manual operations configuration

No external card payment, transfer rail, KYC provider, or credit bureau is configured. `FINANCIAL_PROVIDER_MODE=manual` submits funding and transfers into an audited operations queue; no external authorization is claimed. See `INTEGRATIONS.md`.

## Assets

See [ASSETS.md](ASSETS.md) for exact target paths, formats, dimensions, variants, and fallbacks. The new public shell uses a CSS wordmark until final Valtoria assets are supplied.

## Admin setup

The legacy `is_admin` field is retained for compatibility. The Module 1 migration adds a transitional `role` field and maps current administrators to `super_admin`. Do not grant roles by editing query parameters or exposing database tools publicly.

## Testing

The project includes financial and operations integration tests. Before each deployment:

```powershell
Get-ChildItem -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch '\\vendor\\' } | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
C:\xampp\php\php.exe -r "require 'includes/bootstrap.php'; echo config('app.name');"
$env:DB_DATABASE='valtoria_test'; C:\xampp\php\php.exe tests\financial_core_test.php
$env:DB_DATABASE='valtoria_test'; C:\xampp\php\php.exe tests\module3_operations_test.php
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
Use [DEPLOYMENT.md](DEPLOYMENT.md) as the production release gate.
