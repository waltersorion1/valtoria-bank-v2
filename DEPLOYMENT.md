# Deployment checklist

## Release gate

- Use a supported PHP 8.x runtime with PDO MySQL, Fileinfo, Mbstring, OpenSSL, PHPMailer, and TCPDF.
- Provision MariaDB/MySQL with a least-privilege application user and UTF-8 (`utf8mb4`).
- Back up the target database and test restore. Apply migrations 001–004 in order to a clone first.
- Require both reconciliation queries in `DATABASE.md` to return zero before and after deployment.
- Run PHP lint, `tests/financial_core_test.php`, `tests/module3_operations_test.php`, and authenticated role smoke tests.
- Serve only through HTTPS; set secure cookies, HSTS, CSP, `X-Content-Type-Options`, frame protection, and a restrictive referrer policy at the web server/proxy.
- Set `APP_ENV=production` and `APP_DEBUG=false`. Disable directory listing and PHP execution in every upload location.
- Move identity/avatar storage outside the document root and configure authenticated delivery plus backup/retention policy.
- Remove sample personal data and SQL dumps from deploy artifacts. Coordinate secret/history scrubbing and rotate all historically exposed credentials.
- Create the first `super_admin` through a controlled database/CLI process, then verify every role with a separate test account.
- Keep all feature switches disabled until their operational owner approves launch. `FINANCIAL_PROVIDER_MODE=sandbox` must remain visible unless a reviewed live adapter is installed.
- Configure monitoring for application errors, security events, audit activity, provider failures, reconciliation exceptions, disk capacity, and backups.

## Required environment variables

`APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`, `SESSION_NAME`, `SESSION_IDLE_TIMEOUT`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `MAIL_ENABLED`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `SUPPORT_EMAIL`, `FINANCIAL_PROVIDER_MODE`, `SANDBOX_AUTO_COMPLETE`, and `TRANSFER_STEP_UP_CENTS`.

## Go-live decisions still required

- Licensed operating entity, jurisdictions, required disclosures, privacy/cookie retention, sanctions/AML/KYC obligations, complaint handling, and credit terms.
- Card processor/tokenization/3DS and webhook contract; transfer rail; KYC vendor; email/SMS; secure document storage; credit decision/data providers.
- Approved fee/limit/eligibility values, supported countries, support SLAs, incident response, disaster recovery objectives, audit retention, and reconciliation ownership.
- Final brand assets listed in `ASSETS.md` and legal approval of all customer-facing copy.

Do not characterize the deployment as licensed, insured, PCI compliant, or regulator-approved without independent evidence and written approval.
