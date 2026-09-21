# Valtoria Bank — Module 3 Handover

This document is the continuation prompt for Codex running in the laptop environment.

## Continuation prompt

You are continuing the Valtoria Bank refactor in the repository currently open in this workspace.

First, read these files completely before making changes:

1. `CODEX_BANKING_REFACTOR_MASTER_PROMPT.md`
2. `CODEX_MODULE3_HANDOVER.md`
3. `PROJECT_AUDIT.md`
4. `ARCHITECTURE.md`
5. `DATABASE.md`
6. `SECURITY.md`
7. `TESTING.md`
8. `INTEGRATIONS.md`
9. `ASSETS.md`
10. `CHANGELOG.md`

Modules 1 and 2 are complete. Continue **MODULE 3 only**. The user also requested coherent UI/UX across every page and expanded home-page content. Do not restart earlier modules or rebuild the project from scratch.

The latest pushed checkpoint at the time of this handover is:

```text
e36d6de Uncompleted Module 3
```

The branch is `main`, and this checkpoint is already on `origin/main`. Confirm the current branch, pull safely if necessary, and inspect the working tree before editing. Preserve any newer user changes.

## Work already completed in the Module 3 checkpoint

- Added `database/migrations/004_module3_operations.sql` with:
  - support cases and threaded messages;
  - internal customer notes;
  - initial safe feature-toggle records;
  - operations-oriented indexes.
- Added a reusable operations shell:
  - `includes/admin_header.php`
  - `includes/admin_footer.php`
  - `assets/css/admin-operations.css`
- Added role-to-permission mapping and audit/session helpers in `includes/functions.php`.
- Added session-version enforcement to the customer and admin shells.
- Added/reworked operations pages:
  - `admin/dashboard.php`
  - `admin/customers.php`
  - `admin/customer.php`
  - `admin/document.php`
  - `admin/cards.php`
  - `admin/transactions.php`
  - `admin/credit-applications.php`
  - `admin/settings.php`
  - `admin/support.php`
  - `admin/audit.php`
- Replaced unsafe legacy admin endpoints with redirects to the corresponding new operations pages. The old GET-based mutation paths should no longer execute.
- Added authenticated customer support at `user/support.php` and linked it in the customer navigation.
- Rebuilt `user/profile.php` around the shared customer shell, CSRF protection, server-side validation, password/session invalidation, security-event recording, and MIME-inspected randomized avatar uploads.
- Retired the old standalone upload handler by redirecting `user/upload_picture.php` to the profile page.
- Expanded `index.php` with additional product, journey, account-visibility, security, regional, and CTA content.
- Added `assets/css/public-enhancements.css` and connected it through `includes/public_header.php`.
- Rebuilt `about-us.php`, `contact.php`, and `services.php` with the shared public shell.

## Important: this is an unfinished checkpoint

Do not treat commit `e36d6de` as Module 3 completion. The interruption happened before migrations, linting, tests, HTTP smoke tests, documentation updates, and final security review.

Start by reviewing every changed file in that commit. Several files are intentionally compact, and they need maintainability and correctness review before release.

## Immediate continuation order

1. Run `git status --short --branch` and inspect commit `e36d6de`.
2. Run PHP syntax checks across all first-party PHP files immediately.
3. Review the new permission matrix and every admin endpoint for authorization behavior.
   - `settings.manage`, `roles.manage`, and similar permissions currently rely on the `super_admin` wildcard.
   - Confirm `credit.view` and `credit.manage` behavior for `credit_officer` and auditors.
   - Confirm non-admin users cannot reach any operations endpoint.
4. Review `enforceSessionVersion()` carefully.
   - Confirm successful login/OTP flows store or safely initialize `session_version`.
   - Confirm customer restriction and role changes invalidate existing sessions.
   - Confirm relative login redirects work from both `/admin` and `/user`.
5. Review and test `admin/document.php` path containment, MIME checks, authorization, headers, and audit behavior.
6. Apply migration `004_module3_operations.sql` to a disposable database first.
7. Rebuild a disposable test database from the original schema and apply migrations `001`, `002`, `003`, and `004` in order.
8. Extend the test suite for Module 3, including at minimum:
   - each role/permission boundary;
   - KYC state transitions and audit records;
   - customer restriction/session invalidation;
   - card enable/disable operations;
   - transaction reversal authorization and ledger reconciliation;
   - credit decision authorization;
   - product-setting changes and audit records;
   - support-case ownership and internal-note non-disclosure;
   - secure upload MIME, size, randomized name, and traversal handling;
   - CSRF on every state-changing endpoint;
   - IDOR attempts against customers, support cases, cards, documents, and financial records.
9. Enforce the new feature toggles in the actual server-side services. At the checkpoint, settings are stored and editable, but the financial/customer services have not yet been wired to fail closed when a feature is disabled.
10. Finish UI/UX coherence:
    - inspect every remaining public, auth, customer, and admin page in a browser;
    - migrate remaining public/legal pages to the shared public header/footer where appropriate;
    - make login, registration, OTP, reset, verification, empty, error, and success states visually consistent;
    - confirm mobile/tablet navigation and table behavior;
    - ensure keyboard focus, labels, contrast, semantic headings, and reduced-motion behavior;
    - remove unused legacy CSS/JS/assets only after confirming they have no live references.
11. Revisit `process-contact.php`:
    - retain prepared inserts and CSRF;
    - validate lengths server-side;
    - ensure user text is escaped for HTML email rather than stored pre-escaped;
    - consider rate limiting without exposing whether mail delivery succeeded.
12. Review all other uploads and legacy identity-document paths. Move toward non-executable storage and authenticated delivery; document any migration that cannot safely be automated.
13. Search the entire first-party repository for:
    - raw/interpolated SQL;
    - state changes through GET;
    - missing CSRF;
    - unescaped output;
    - IDOR/object ownership gaps;
    - direct legacy balance mutations;
    - plaintext secrets or sensitive logs;
    - raw PAN/CVV fields;
    - Nexus branding, peso symbols, obsolete investment/loan links;
    - dead routes and missing assets.
14. Add obvious missing indexes only through a new versioned migration if migration `004` has already been applied anywhere. Never silently rewrite an applied migration.
15. Update all required documentation and add `DEPLOYMENT.md`:
    - `README.md`
    - `PROJECT_AUDIT.md`
    - `ARCHITECTURE.md`
    - `DATABASE.md`
    - `ASSETS.md`
    - `SECURITY.md`
    - `CHANGELOG.md`
    - `INTEGRATIONS.md`
    - `TESTING.md`
    - `DEPLOYMENT.md`
16. Apply the verified migration to the local development database only after backing it up.
17. Run final lint, integration tests, authorization/security checks, reconciliation queries, and authenticated HTTP smoke tests for public, customer, and each practical admin role.
18. Confirm global ledger sum is zero and every account balance matches its customer-ledger entries.
19. Commit the completed Module 3 with a clear message and push `main` to the configured origin.
20. Provide the exact Module 3 final checkpoint requested in the master prompt.

## Known concerns to inspect before continuing

- Migration `004` had not been applied or tested when the checkpoint was interrupted.
- No syntax or runtime verification was completed after the Module 3 edits.
- `admin/customer.php` updates `id_verifications` only when a record already exists; define the intended behavior for customers without a submitted identity record.
- Operations transaction reversal is deliberately an immutable correcting entry, but the new UI and permissions still require end-to-end testing.
- Product toggles are currently control-plane records only; server-side enforcement is unfinished.
- Support email notifications were not added; in-app support is the authoritative implemented channel for now.
- Support attachments were intentionally not implemented because a complete safe attachment pipeline was not yet built.
- Existing `contact_messages` remain separate from authenticated support cases.
- Existing legacy identity files may still live beneath a web-visible uploads directory.
- The new avatar folder may be created at runtime; confirm web-server execution is disabled for all upload directories.
- The public header remains desktop-oriented; a coherent accessible mobile navigation still needs review.
- Some old public/legal/auth pages still use legacy layouts and CSS.
- Old CSS, JavaScript, images, SQL dumps, logs, and temporary files have not yet been removed because usage and retention must be verified first.
- `mail_debug.log` may contain operational data and must be reviewed/redacted/ignored appropriately without deleting evidence blindly.
- Real card, transfer, KYC, email, SMS, and credit-provider integrations still require actual credentials and provider documentation. Do not simulate live processing.

## Non-negotiable constraints

- Use integer USD cents for authoritative money values.
- Preserve the immutable ledger and reversal model.
- Never store or log full PAN, CVV, passwords, OTPs, reset tokens, or provider secrets.
- Every state-changing operation must use POST, CSRF protection, server-side validation, authorization, and appropriate auditing.
- Never expose internal support notes to customers.
- Do not invent regulatory approval, deposit insurance, licenses, partner banks, addresses, or card-network endorsement.
- Keep sandbox activity explicitly labeled until real provider integrations exist.
- Do not begin work outside Module 3.
- Do not declare Module 3 complete until all required checks and documentation are finished.

## Required final report

At completion, provide:

- complete feature inventory;
- architecture summary;
- database/migration summary;
- security review summary;
- tests and checks performed;
- deployment checklist;
- required environment variables;
- required assets with exact paths;
- real integrations still needed;
- remaining technical debt;
- remaining compliance/legal/business inputs that cannot be inferred from code;
- final commit hash and push result.

