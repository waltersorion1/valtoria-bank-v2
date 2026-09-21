# Testing

## Automated foundation

`tests/financial_core_test.php` exercises cents parsing, fee behavior through real services, card metadata storage, funding and transfer idempotency, atomic balance movements, Visa credit eligibility, application approval, disbursement, repayment, and ledger reconciliation.

Run it only against a disposable database:

```powershell
$env:DB_DATABASE='valtoria_test'
C:\xampp\php\php.exe tests\financial_core_test.php
```

Build `valtoria_test` from a scrubbed legacy schema, then apply migrations `001`, `002`, and `003`. Never point integration tests at production.
Apply migration `004`, then run `tests/module3_operations_test.php` to validate the role matrix, fail-closed settings, and support-note disclosure boundary.

The Module 3 release pass linted 112 first-party PHP files, rebuilt a disposable MariaDB database through migrations 001–004, ran both integration scripts, reconciled the ledger and cached balances to zero differences, and smoke-tested all public routes plus unauthenticated customer/admin redirects.

## Manual checks

- Submit Visa and Mastercard metadata, approve compatibility, and verify no PAN or CVV is stored.
- Submit funding, move it through processing and completion, and verify duplicate-submit protection.
- Add a beneficiary, quote a transfer, confirm it, and verify amount/fee/receipt.
- Attempt self-transfer, insufficient funds, invalid amount, and daily-limit cases.
- Verify credit stays unavailable without KYC plus a verified Visa card and configured history.
- Review an application as `super_admin` or `credit_officer`, accept it as the customer, and post a repayment.
- Filter transactions, open a receipt, and download a CSV statement.
