# Valtoria Bank — Codex Master Refactor & Product Build Prompt

## Mission

You are acting as the senior engineer and product team responsible for transforming the **already-cloned Nexus Bank PHP project** into **Valtoria Bank**, a polished, production-minded, bank-card-focused financial platform.

You are not starting from a blank repository. **Audit, preserve, refactor, and improve the existing codebase.** Reuse working Nexus functionality when it is sound, but treat every existing file, database query, session flow, admin action, and financial calculation as untrusted legacy code until reviewed.

The end result must feel like a real premium fintech/banking product rather than a reskinned demo or student project.

---

# 1. Product Identity

## Brand name

**Valtoria Bank**

## Product positioning

Valtoria Bank is a **bank-card-focused financial service** centered on:

- card-to-card transfers;
- Visa-card funding;
- Mastercard-card funding;
- account and transaction management;
- credit products available only to eligible Visa cardholders;
- secure customer onboarding, KYC, account security, statements, notifications, support, and financial insights.

Brand positioning copy may state that Valtoria Bank is **headquartered in the United States with a regional office in Cameroon and serves customers across Africa, Europe, and Asia**, but do not invent a street address, banking charter, license number, regulator, deposit-insurance status, partner bank, card-network membership, or other legal claim that is not present in configuration/content supplied by me.

Do not falsely imply that Visa or Mastercard endorses, owns, operates, or sponsors Valtoria Bank. Use their names only to describe supported card-network functionality where technically and legally appropriate.

## Currency

The initial platform currency is **USD only**.

All authoritative money values must use **integer USD cents** in application logic and the database wherever practical.

Examples:

```text
$1.00     -> 100
$125.67   -> 12567
$10,000   -> 1000000
```

Build clean domain boundaries so multicurrency can be added later, but do not implement exchange-rate conversion in this refactor unless explicitly requested.

---

# 2. Non-Negotiable Development Strategy

1. **Do not rebuild the project from scratch.**
2. Begin by recursively inspecting the cloned Nexus repository.
3. Preserve useful functionality and migrate it into a cleaner architecture.
4. Replace unsafe or unusable legacy components deliberately.
5. Keep the application runnable throughout the refactor.
6. Prefer the current technology stack:
   - PHP 8+
   - MySQL/MariaDB
   - PDO
   - HTML5
   - modern CSS
   - vanilla JavaScript
7. Do not introduce Laravel, Symfony, React, Vue, or another major framework unless I explicitly request it.
8. Avoid creating duplicate helpers, services, layouts, tables, or components. Search the repository before adding new ones.
9. Run syntax checks/tests after each coherent batch of changes.
10. Make changes in the **three modules defined later in this document**. Do not blur module boundaries.

A sensible target structure may resemble:

```text
/config
/app
    /Controllers
    /Models
    /Repositories
    /Services
    /Middleware
    /Validation
    /Helpers
    /Security
/public
    /assets
        /css
        /js
        /images
        /icons
/views
    /layouts
    /partials
    /public
    /auth
    /customer
    /admin
/database
    /migrations
    /seeds
/storage
    /logs
    /uploads
    /exports
```

Do not reorganize files only for aesthetics. Migrate toward a cleaner structure when it reduces duplication, improves security, or makes the project easier to maintain.

---

# 3. First Action: Repository Audit

Before major edits, inspect the complete project and create a root-level `PROJECT_AUDIT.md`.

Audit at minimum:

- application entry points;
- routes/page flow;
- public pages;
- authentication pages;
- customer dashboard;
- admin area;
- database configuration;
- schema and SQL dumps;
- user/account tables;
- card-related code;
- transfer logic;
- deposit/funding logic;
- withdrawal logic;
- loan/credit logic;
- investment code inherited from Nexus;
- transaction history;
- OTP/email flows;
- PDF/receipt generation;
- uploads;
- AJAX/API endpoints;
- CSS architecture;
- JavaScript architecture;
- third-party libraries;
- cron/background tasks;
- error handling;
- logging;
- role/permission logic;
- security-sensitive server actions.

Search specifically for:

- raw SQL interpolation;
- SQL injection risks;
- XSS;
- missing escaping;
- CSRF weaknesses;
- IDOR/broken object authorization;
- hardcoded passwords/API keys;
- insecure sessions;
- plaintext or weak password storage;
- unsafe password reset logic;
- insecure OTP handling;
- insecure file uploads;
- missing server-side validation;
- client-controlled amounts/balances;
- floating-point money calculations;
- race conditions;
- balance mutations without DB transactions;
- duplicate transaction/replay risks;
- missing audit logs;
- privilege escalation paths;
- sensitive information in logs or URLs.

`PROJECT_AUDIT.md` must contain:

- current architecture;
- current feature inventory;
- database overview;
- security findings grouped by severity;
- code-quality findings;
- UI/UX findings;
- reusable components worth keeping;
- components to refactor;
- components to replace/remove;
- proposed target architecture;
- database migration strategy;
- the three-module implementation plan.

Do not perform a blind visual reskin before completing this audit.

---

# 4. Valtoria Bank Product Architecture

Design the application around clear financial domains rather than disconnected CRUD pages.

Core domains should include where appropriate:

```text
Customer
Identity / KYC
Account
Balance / Ledger
Card Profile
External Funding Instrument
Beneficiary
Card-to-Card Transfer
Card Funding
Transaction
Fee
Limit
Statement
Credit Eligibility
Credit Application
Credit Facility / Loan
Repayment
Notification
Support Case
Security Event
Admin Action
Audit Log
Provider / Webhook Event
```

Not every concept needs its own class or table if the current architecture does not justify it, but the business logic should remain clearly separated.

---

# 5. Public Website & Content Strategy

Build a complete public-facing Valtoria Bank website, not only login/dashboard pages.

## Recommended public pages

Create/refactor the following as appropriate:

- Home
- Personal Banking / Accounts
- Cards
- Card-to-Card Transfers
- Visa Card Funding
- Mastercard Card Funding
- Credit
- Security
- About Valtoria
- Help Center / FAQ
- Contact / Support
- Login
- Open an Account / Register
- Privacy Policy
- Terms of Service
- Cookie Policy
- Card & Funding Terms placeholder
- Credit Terms / Disclosures placeholder

Use polished content rather than lorem ipsum.

## Content tone

All copy should be:

- premium;
- concise;
- calm;
- financially literate;
- trustworthy;
- globally understandable;
- free from hype and unrealistic promises.

Avoid phrases such as:

- “guaranteed approval”;
- “risk-free returns”;
- “instant money with no checks”;
- fabricated security or regulatory claims.

A suitable Valtoria tone is: **clear, controlled, modern, global, secure, and service-oriented**.

### Suggested homepage messaging direction

Hero direction:

> **Move money with confidence.**  
> Fund, transfer, manage cards, and access eligible credit products from one secure financial platform.

Supporting product pillars may include:

- Send card-to-card
- Fund with Visa or Mastercard
- Track every transaction
- Access credit when eligible
- Manage security from one place

Do not hardcode this exact copy everywhere. Treat it as direction and write page-specific content naturally.

---

# 6. Customer Registration, Authentication & Onboarding

Create a professional customer onboarding flow.

## Registration

Include fields appropriate to a financial platform, such as:

- first name;
- middle name optional;
- last name;
- email;
- phone number with international country code;
- date of birth;
- country of residence;
- nationality where required;
- residential address;
- city/region/postal code where applicable;
- password + confirmation;
- acceptance of terms/privacy;
- marketing preference kept separate from mandatory legal consent.

Do not request data merely because a banking form “usually has it.” Use server-side validation and make sensitive fields purposeful.

## Authentication

Implement/refactor:

- login;
- logout;
- email verification;
- password reset;
- session management;
- optional email OTP/2FA foundation;
- trusted-device/session view if practical;
- login/security event history;
- rate limiting/throttling.

Use `password_hash()` and `password_verify()`.

Regenerate session IDs after authentication and privilege changes.

---

# 7. KYC & Customer Profile

Provide a complete profile and identity area.

Customer features may include:

- personal information;
- contact details;
- address;
- identity-document status;
- KYC status;
- document upload where the project supports safe uploads;
- account preferences;
- notification preferences;
- security settings;
- active sessions;
- password change;
- 2FA management if enabled;
- support access.

Suggested KYC states:

```text
not_started
pending
under_review
verified
rejected
needs_information
expired
```

Do not claim actual verification from an external provider unless a real provider integration is configured.

---

# 8. Customer Dashboard

The logged-in dashboard must feel like a premium banking application.

Include appropriate components such as:

- primary available balance;
- account/card summary;
- recent transactions;
- quick transfer action;
- quick card-funding action;
- credit eligibility/status card;
- current credit balance or next payment when applicable;
- monthly money-in/money-out summary;
- transaction category or activity chart using real backend data;
- card/funding limits;
- verification/KYC status;
- unread notifications;
- security alerts;
- recent login information;
- support shortcut.

Do not fill charts with fabricated numbers in production mode. Empty accounts should receive polished empty states and onboarding guidance.

---

# 9. Accounts, Balances & Ledger

Financial correctness takes priority over UI convenience.

## Authoritative rules

- Use integer cents for authoritative USD values.
- Never trust balances or fees sent by the browser.
- Never use JavaScript floating point as the source of truth.
- Avoid PHP floating point for authoritative money operations.
- Centralize money conversion/formatting.
- Use database transactions for monetary mutations.
- Use row locking or equivalent protection where concurrent balance changes could conflict.
- Implement idempotency for operations that may be submitted twice.
- Completed financial records must be immutable; corrections should use reversal/adjustment transactions.

Prefer a traceable ledger model.

Each financial event should ultimately be reconcilable to ledger entries and an auditable transaction record.

Suggested transaction states:

```text
draft
pending
processing
completed
failed
cancelled
reversed
```

Use only the states required by the real flow.

Each transaction should have:

- internal ID;
- unique human-readable reference;
- customer/account;
- type;
- amount cents;
- fee cents;
- net amount cents if relevant;
- source;
- destination;
- status;
- description/memo;
- provider reference when applicable;
- timestamps;
- failure/reversal metadata where applicable.

---

# 10. Cards & Card Management

Valtoria Bank is card-focused, so this area must be first-class.

## Card center

Customer card features should include when supported:

- view linked/registered cards;
- add a card;
- verify a card through the configured provider flow;
- masked card display;
- cardholder name;
- network: Visa / Mastercard;
- expiry month/year where appropriate;
- billing-address metadata if required;
- default funding card;
- card status;
- remove/deactivate linked card where safe;
- card activity;
- funding/transfer limits;
- card-specific security information.

## PCI/card-data rules

Do **not** design the application to store raw card numbers or CVV unless a compliant payment architecture explicitly requires it and I provide the relevant provider/PCI design.

Default architecture:

- use a PSP/gateway token;
- store provider customer/payment-method token;
- store network;
- store last four digits;
- store cardholder label/name if needed;
- store expiry only if provider architecture permits;
- never store CVV;
- never log full PAN;
- never place card details in URLs;
- mask card information in UI/logs.

If the existing Nexus project stores sensitive card details unsafely, mark it as a critical finding and migrate away from that design.

---

# 11. Visa & Mastercard Card Funding

Create a clear card-funding workflow.

Customer journey should generally be:

```text
Choose funding destination
-> choose/add card
-> enter amount
-> show fee and total
-> confirm
-> provider authorization/3DS if applicable
-> pending/processing
-> completed or failed
-> receipt + notification
```

Funding may be labeled by network where appropriate:

- Fund with Visa
- Fund with Mastercard

But the actual payment flow must be provider-driven, not simulated as a real charge.

Create a provider/service interface so the application can operate in:

```text
sandbox / development
live provider mode
```

If no real provider API/credentials are present, implement the domain flow and sandbox adapter only. Do not fake a production authorization.

Funding records should include:

- user;
- destination account/card;
- provider token/reference;
- funding network;
- amount cents;
- fee cents;
- total charged cents;
- status;
- timestamps;
- provider response metadata safe for storage;
- idempotency key;
- failure reason code where appropriate.

---

# 12. Card-to-Card Transfers

Create a robust card-to-card transfer experience.

Customer functionality should include:

- select eligible source card/account;
- select or add a beneficiary/destination;
- enter transfer amount;
- optional memo/reference;
- show applicable fee;
- show transfer limits;
- confirmation step;
- OTP/step-up security for high-risk transfers if configured;
- processing status;
- receipt;
- transaction details;
- repeat transfer;
- beneficiary management;
- clear failure/reversal states.

Do not directly mutate balances from a form submission without an auditable transfer service and database transaction.

Use deterministic server-side checks for:

- ownership/authorization;
- card/account status;
- KYC state if required;
- balance/available amount;
- per-transaction limit;
- daily/monthly limit;
- destination validity;
- duplicate request;
- fee calculation;
- provider status where relevant.

---

# 13. Credit Products for Eligible Visa Cardholders

Credit is a core Valtoria Bank feature, but access is restricted to **eligible Visa cardholders**.

## Customer credit center

Build a dedicated credit area containing appropriate elements such as:

- Visa-card eligibility status;
- eligibility explanation;
- available credit product(s);
- maximum eligible amount when determined by configured rules/provider data;
- estimated repayment/term display;
- application form;
- application status;
- approved amount;
- disbursement status;
- active credit/loan overview;
- repayment schedule;
- outstanding principal;
- accrued/charged interest where applicable;
- fees where applicable;
- next payment;
- repayment history;
- downloadable agreement/statement placeholder where configured.

## Eligibility rules

The application may implement configurable deterministic eligibility rules such as:

- verified customer/KYC status;
- verified Visa card;
- account age;
- transaction history thresholds;
- prior repayment history;
- configured limits;
- jurisdiction/service availability;
- manual admin underwriting.

Do not invent a “credit bureau score” or claim external bureau data unless a real bureau/API integration exists.

Do not use protected/sensitive personal characteristics as scoring variables.

## Credit lifecycle

A useful lifecycle is:

```text
not_eligible / eligible
-> application
-> under_review
-> approved / rejected / needs_information
-> accepted
-> disbursement
-> active
-> repaid / closed / overdue / defaulted
```

Use only states supported by the actual implementation.

All terms shown to customers must match the stored/calculated terms used by the system.

---

# 14. Transactions, Statements & Receipts

Create a high-quality transaction center.

Features should include:

- paginated transaction history;
- search;
- date filtering;
- type filtering;
- status filtering;
- amount filtering when practical;
- transaction details;
- human-readable references;
- funding vs transfer vs repayment identification;
- fees shown separately;
- receipts;
- downloadable statement/export where practical.

Statements and receipts should contain consistent Valtoria Bank branding without inventing regulatory details.

---

# 15. Notifications & Communication

Centralize notification handling.

Support appropriate in-app and email notifications for:

- account verification;
- login/security event;
- password reset;
- card added/verified;
- funding initiated/completed/failed;
- transfer initiated/completed/failed/reversed;
- beneficiary changes;
- credit eligibility update;
- credit application status;
- credit disbursement;
- payment due;
- repayment received;
- KYC status;
- support updates;
- system announcements.

Centralize email sending rather than scattering PHPMailer calls throughout individual pages.

No SMTP/API credentials may be committed to the repository.

---

# 16. Financial Tools & Analytics

Add practical customer-facing financial utilities that make sense for Valtoria Bank without turning the product into an unrelated personal-finance app.

Useful features may include:

- monthly inflow/outflow overview;
- transfer/funding trend chart;
- fee summary;
- credit repayment progress;
- account activity insights;
- statement-period summaries;
- upcoming payment reminders;
- transaction search/export;
- configurable spending/activity categories if supported cleanly.

All totals and charts must be derived from backend-authoritative records.

---

# 17. Support Center

Build a proper support experience.

Customer features may include:

- FAQ/help center;
- contact form;
- support tickets/cases;
- issue category;
- priority/status;
- message thread;
- attachment support only if safely implemented;
- ticket history.

Do not expose internal admin notes to customers.

---

# 18. Admin & Operations Platform

The admin side must look and behave like a professional financial operations console, not a generic CRUD template.

## Admin dashboard

Use real database data for metrics such as:

- total customers;
- verified customers;
- pending KYC reviews;
- active cards/linkages;
- transfer volume;
- funding volume;
- completed/failed transactions;
- active credit accounts;
- outstanding credit balance;
- overdue repayments;
- pending support cases;
- recent security events.

Where meaningful, include date-range filters and clean charts.

## Customer management

Admins with appropriate permissions should be able to:

- search/filter customers;
- view a customer profile;
- review KYC status;
- view accounts;
- view linked-card metadata safely;
- review transaction history;
- review credit history;
- review support history;
- suspend/restrict/reactivate according to permission;
- add internal administrative notes;
- see audit trail.

Never display full PAN/CVV.

## KYC operations

Provide:

- review queue;
- document/status review;
- needs-information flow;
- approve/reject actions;
- reason/note capture;
- audit logging.

## Card operations

Provide appropriate controls for:

- registered/linked cards;
- verification status;
- provider token/reference metadata;
- network;
- card state;
- customer ownership;
- limits;
- funding activity;
- risk/review flags;
- disable/re-enable where the product model allows.

## Transfer & funding operations

Provide:

- search/filter;
- transaction detail;
- provider status/reference;
- fee breakdown;
- source/destination metadata;
- failure/reversal information;
- review flags;
- reconciliation status;
- safe retry only where idempotency and provider behavior make retry valid;
- manual administrative correction only through audited adjustment/reversal flows.

Admins must **not** be given an unsafe “edit completed transaction amount” form.

## Credit operations

Provide:

- eligibility review;
- application queue;
- application detail;
- underwriting notes;
- requested amount;
- approved amount;
- term/rate/fee data;
- approve/reject/needs-information;
- disbursement state;
- active credit monitoring;
- repayment schedule;
- overdue accounts;
- payment history;
- close/write-off states only when deliberately modeled;
- full audit history.

## Limits, fees & product configuration

Where the product needs configuration, provide role-protected settings for items such as:

- transfer minimum/maximum;
- funding minimum/maximum;
- daily/monthly limits;
- transfer fees;
- funding fees;
- credit product limits;
- credit terms;
- maintenance/feature toggles;
- supported regions/countries;
- notification templates.

Do not scatter fee percentages and limits through PHP files.

## Support administration

Provide:

- ticket queue;
- filters;
- assignment;
- status;
- replies;
- internal notes;
- attachments if safe;
- customer context.

## Roles & permissions

Do not implement “admin” as one unrestricted boolean if the codebase can support a safer model.

Prefer roles/permissions such as:

```text
super_admin
operations
kyc_reviewer
credit_officer
support_agent
read_only_auditor
```

Use the minimum practical permission system. Every protected endpoint must verify authorization server-side.

## Audit logs

Record sensitive admin events such as:

- customer status change;
- KYC decision;
- card state change;
- limit/fee change;
- credit decision;
- reversal/adjustment;
- role/permission change;
- security configuration change;
- login/security event.

Audit records should capture actor, action, target, timestamp, and safe metadata.

---

# 19. Security Baseline

Security is part of the implementation, not a final cosmetic pass.

At minimum review/implement:

- PDO prepared statements;
- strict server-side validation;
- output escaping;
- CSRF protection for state-changing requests;
- secure session cookies;
- `HttpOnly`;
- `SameSite`;
- `Secure` in HTTPS production;
- session regeneration after login;
- login throttling;
- secure password-reset tokens;
- expiring/single-use OTPs;
- authorization on every protected object/action;
- IDOR protection;
- mass-assignment protection;
- no plaintext passwords;
- no credentials in Git;
- `.env` or equivalent secret configuration;
- production-safe error handling;
- technical logs separate from financial/admin audit logs;
- safe file MIME/type/size checks;
- randomized upload filenames;
- uploads outside executable web paths where possible;
- rate limiting for sensitive endpoints;
- idempotency for financial commands;
- DB transactions and concurrency protection;
- webhook signature verification when real providers are added;
- replay protection for provider webhooks;
- sensitive-data redaction in logs.

Create/update `SECURITY.md` with implemented controls and remaining risks.

Do not claim that the application is “bank-grade,” “PCI compliant,” “FDIC insured,” “fully secure,” or regulator-approved unless independently established and explicitly supplied.

---

# 20. Database & Migration Rules

Inspect the existing Nexus schema before changing it.

Prefer versioned migrations over repeatedly replacing one monolithic SQL dump.

Use where appropriate:

- primary keys;
- foreign keys;
- unique constraints;
- indexes;
- timestamps;
- explicit status columns;
- safe soft-delete/status patterns for customer entities;
- immutable financial records;
- audit records.

Do not cascade-delete financial history casually.

After each schema change:

1. add/update migration;
2. update `DATABASE.md`;
3. search the repository for old table/column names;
4. run relevant checks;
5. confirm financial records remain reconcilable.

---

# 21. Valtoria Graphic Chart / Design System

You are responsible for the complete visual direction.

The desired look is **premium international banking**: calm, precise, modern, spacious, trustworthy, and data-focused.

Avoid the appearance of a crypto casino, generic admin template, school project, or neon fintech landing page.

## Brand color direction

Use this as the default design token direction unless the existing logo requires a small adjustment:

```css
--valtoria-navy:       #081426;
--valtoria-navy-2:     #10233F;
--valtoria-blue:       #155EEF;
--valtoria-blue-dark:  #0B4ACB;
--valtoria-surface:    #FFFFFF;
--valtoria-bg:         #F6F8FB;
--valtoria-text:       #101828;
--valtoria-muted:      #667085;
--valtoria-border:     #E4E7EC;
--valtoria-success:    #079455;
--valtoria-warning:    #DC6803;
--valtoria-danger:     #D92D20;
--valtoria-info:       #175CD3;
```

Use color semantically and consistently.

Do not overuse gradients. If used at all, restrict them to subtle brand/hero accents.

## Typography

Prefer a clean modern sans-serif such as **Inter** or the closest locally available equivalent, with system fallbacks.

Use clear hierarchy:

- display/hero;
- H1/H2/H3;
- body;
- small/meta;
- tabular financial numerals.

Financial values should be easy to scan and align.

## Component language

Create consistent reusable styling for:

- top navigation;
- authenticated sidebar;
- mobile navigation;
- account/balance cards;
- card visualizations;
- buttons;
- icon buttons;
- forms;
- amount inputs;
- tables;
- filters;
- tabs;
- badges;
- alerts;
- modals/drawers;
- OTP inputs;
- stat tiles;
- charts;
- transaction rows;
- step/progress flows;
- empty states;
- loading/skeleton states;
- error states;
- confirmation screens;
- receipts;
- admin panels.

Use one icon family consistently. Reuse an existing suitable library if the Nexus project already has one; otherwise choose a lightweight option rather than loading multiple icon sets.

## Layout

Public website:

- high-quality responsive header;
- generous content width;
- clear product sections;
- professional footer;
- strong but restrained hero sections.

Customer app:

- desktop sidebar + top utility bar;
- responsive mobile navigation;
- dashboard grid;
- clean tables/list cards;
- predictable action placement.

Admin app:

- visually related to Valtoria but clearly operational;
- dense enough for efficient work without clutter;
- robust filters/tables/details panels;
- distinct warning styles for destructive actions.

## Interaction

Use subtle motion only when it improves comprehension.

Avoid:

- bouncing elements;
- excessive hover animations;
- animated counters without purpose;
- giant shadows;
- random border radii;
- glassmorphism everywhere;
- emoji-heavy interface copy.

Accessibility must include:

- semantic markup;
- keyboard navigation;
- visible focus states;
- form labels;
- accessible contrast;
- clear error messages;
- reduced-motion consideration where practical.

---

# 22. Image & Asset Handling

**Do not generate or invent final image assets.**

Instead, inspect the project and create/update a root-level `ASSETS.md` telling me exactly where every visual asset should be placed and what it is used for.

Use a clean convention such as:

```text
/assets/images/logo.png
/assets/images/logo-light.png
/assets/images/logo-mark.png
/public/assets/images/brand/favicon.ico
/public/assets/images/brand/apple-touch-icon.png
/public/assets/images/marketing/home-hero.webp
/public/assets/images/marketing/cards-hero.webp
/public/assets/images/marketing/transfers-hero.webp
/public/assets/images/marketing/credit-hero.webp
/public/assets/images/marketing/about-office.webp
/public/assets/images/marketing/security-illustration.webp
/public/assets/images/placeholders/avatar-default.webp
/public/assets/icons/
```

`ASSETS.md` should specify for each asset:

- exact path;
- recommended format;
- recommended dimensions/aspect ratio;
- where it appears;
- dark/light variant requirements;
- fallback behavior.

If logo/favicon files already exist, identify their current paths and either keep them or document the exact target paths for migration.

Use elegant neutral placeholders, CSS shapes, or layout-safe fallback blocks until I provide final images.

Never hotlink random third-party images into production pages.

---

# 23. Provider Integrations

Create clean interfaces/adapters for integrations rather than embedding provider-specific logic inside controllers/pages.

Potential future provider categories:

- card payment gateway;
- Visa/Mastercard funding processor;
- card-to-card transfer rail/provider;
- KYC/identity verification;
- email;
- SMS/OTP;
- credit bureau/decision service;
- document storage.

Until I provide real credentials and API documentation:

- keep provider mode in sandbox/development;
- use safe local test adapters;
- document configuration points;
- do not pretend a real financial transfer occurred.

Create `INTEGRATIONS.md` if provider abstraction becomes substantial.

---

# 24. Error Handling, Logging & Observability

Production users must never see:

- SQL errors;
- stack traces;
- credentials;
- server paths;
- provider secrets;
- raw exception dumps.

Create centralized error handling and structured logging where practical.

Keep separate concepts for:

- application error logs;
- security-event logs;
- financial transaction records;
- admin audit logs;
- provider/webhook logs.

Redact sensitive financial/card data from all logs.

---

# 25. Testing & Verification

Prioritize tests/checks around high-risk logic.

At minimum cover or manually verify:

- registration;
- login/logout;
- password reset;
- session hardening;
- role authorization;
- CSRF protection;
- cents conversion;
- fee calculations;
- insufficient funds;
- double submission/idempotency;
- card ownership checks;
- card funding state transitions;
- card-to-card transfers;
- transaction reversals;
- credit eligibility rules;
- credit calculations;
- repayment posting;
- admin permission boundaries;
- KYC transitions;
- upload validation;
- secure masking of card details.

If the repository has no test framework, introduce a lightweight maintainable foundation only where useful. Do not spend the whole refactor installing a large testing stack.

After editing PHP files, run PHP syntax checks on modified files when possible.

---

# 26. Documentation to Maintain

Maintain these root-level files during the refactor:

```text
README.md
PROJECT_AUDIT.md
ARCHITECTURE.md
DATABASE.md
ASSETS.md
SECURITY.md
CHANGELOG.md
```

Add when appropriate:

```text
INTEGRATIONS.md
TESTING.md
DEPLOYMENT.md
```

`README.md` must ultimately include:

- project overview;
- requirements;
- installation;
- database setup;
- migrations;
- environment configuration;
- local development;
- email configuration;
- provider/sandbox configuration;
- asset placement;
- admin setup;
- testing;
- deployment notes.

Never place real credentials in documentation.

---

# 27. EXACT THREE-MODULE EXECUTION PLAN

The entire transformation must be executed in **three modules**. Keep the project stable and consistent at each module boundary.

By default, **finish one module, run checks, produce a checkpoint summary, then stop and wait for my instruction before beginning the next module**. This prevents uncontrolled large rewrites and keeps work reviewable.

---

## MODULE 1 — Audit, Foundation, Architecture & Valtoria Design System

### Objective

Turn the cloned Nexus project into a reliable Valtoria Bank foundation without yet attempting every financial feature.

### Work in this module

1. Audit the entire repository.
2. Create `PROJECT_AUDIT.md`.
3. Identify and fix blockers preventing the existing app from running.
4. Establish safe environment/config handling.
5. Clean database connection/PDO usage.
6. Introduce centralized helpers/services where clearly needed.
7. Harden authentication/session handling.
8. Add CSRF and validation foundations.
9. Establish authorization/role architecture.
10. Establish error handling/logging.
11. Create target folder/layout strategy without unnecessary mass moves.
12. Rebrand the project to **Valtoria Bank**.
13. Build the global design system and reusable page shell.
14. Refactor public navigation/footer.
15. Build/refine key public pages.
16. Refactor registration/login/password-reset experience.
17. Build/refine customer profile/KYC/security foundations.
18. Create customer dashboard shell using real existing data.
19. Create admin shell and navigation.
20. Create/update `ASSETS.md` with exact asset-placement instructions.
21. Create/update architecture/database/security documentation.

### Module 1 definition of done

- project runs;
- no obvious fatal blockers;
- brand changed coherently to Valtoria Bank;
- design system is consistent;
- public site/auth/dashboard/admin shells are professional and responsive;
- authentication/session baseline is improved;
- configuration/secrets strategy exists;
- audit/documentation exists;
- asset paths are documented;
- no major financial behavior has been silently broken.

### End-of-module checkpoint

Report:

- audit summary;
- files changed;
- migrations created;
- security changes;
- design/UI changes;
- assets I need to provide and their paths;
- tests/checks run;
- known remaining risks;
- exact scope proposed for Module 2.

Then stop and wait for my approval before Module 2.

---

## MODULE 2 — Financial Core, Cards, Funding, Transfers & Credit

### Objective

Build the core Valtoria financial product on top of the stabilized Module 1 foundation.

### Work in this module

1. Convert/refactor monetary logic to authoritative USD cents.
2. Establish ledger/account transaction integrity.
3. Add human-readable financial references.
4. Add atomic DB transactions and idempotency protections.
5. Refactor transaction history/details/receipts.
6. Build the complete Card Center.
7. Implement safe linked-card metadata/token model.
8. Remove unsafe raw-card storage if found.
9. Build Visa card funding workflow.
10. Build Mastercard card funding workflow.
11. Build card-to-card transfer workflow.
12. Add beneficiary management.
13. Add fees and limits through centralized configuration.
14. Add funding/transfer confirmation and status screens.
15. Add notifications for financial events.
16. Build the Visa-card credit eligibility system.
17. Build credit applications and admin review workflow.
18. Build approved credit/disbursement lifecycle.
19. Build repayment schedule/payment history logic.
20. Integrate all monetary events into the authoritative transaction/ledger model.
21. Add customer financial analytics derived from real transaction data.
22. Add statements/export where practical.
23. Add provider interfaces and sandbox adapters where external rails are unavailable.

### Module 2 definition of done

- money logic uses cents consistently;
- card data handling follows the token/masked-data model;
- Visa/Mastercard funding workflows are coherent;
- card-to-card transfers are auditable and protected from duplicate submission;
- credit is available only through configured Visa-card eligibility rules;
- transaction, fee, credit, and repayment records reconcile correctly;
- external rails remain sandbox/provider abstractions until real APIs are supplied.

### End-of-module checkpoint

Report:

- financial architecture changes;
- schema/migrations;
- card-data/security changes;
- funding flow status;
- transfer flow status;
- credit workflow status;
- provider integrations still requiring real credentials/docs;
- tests/checks run;
- reconciliation findings;
- remaining risks;
- exact scope proposed for Module 3.

Then stop and wait for my approval before Module 3.

---

## MODULE 3 — Admin Operations, Security Hardening, QA & Launch Readiness

### Objective

Turn the application from a feature-complete build into a polished operations-ready product.

### Work in this module

1. Complete admin dashboard analytics.
2. Complete customer/KYC operations.
3. Complete card operations.
4. Complete transfer/funding operations.
5. Complete credit operations.
6. Add fees/limits/product configuration.
7. Complete support-ticket administration.
8. Complete roles/permissions.
9. Complete audit/security logs.
10. Add reconciliation/reporting views where practical.
11. Add system settings and safe feature toggles.
12. Conduct a full authorization review.
13. Conduct a full SQL/XSS/CSRF/IDOR review.
14. Review all upload handling.
15. Review sensitive logging/redaction.
16. Review concurrency/idempotency.
17. Review accessibility.
18. Review mobile/tablet responsiveness.
19. Review loading/empty/error/success states.
20. Remove dead Nexus branding and obsolete legacy code only when confirmed unused.
21. Optimize obvious performance issues and DB indexes.
22. Complete testing/manual test checklist.
23. Finalize documentation.
24. Create deployment checklist.
25. Produce a final list of remaining provider/compliance/business decisions that require real-world input.

### Module 3 definition of done

- admin console is coherent and role-protected;
- sensitive actions are audited;
- customer and admin UI are visually consistent;
- critical financial paths have been tested;
- no known raw secrets/card data are exposed;
- documentation is current;
- deployment requirements are clear;
- unresolved external-provider or regulatory dependencies are explicitly identified rather than faked.

### Final checkpoint

Provide:

- complete feature inventory;
- architecture summary;
- database/migration summary;
- security review summary;
- tests/checks performed;
- deployment checklist;
- required environment variables;
- required assets and exact paths;
- real integrations still needed;
- remaining technical debt;
- remaining compliance/legal inputs that cannot be inferred from code.

---

# 28. Codex Working Rules

Follow these throughout all three modules:

1. Inspect before editing.
2. Understand how a file is used before changing it.
3. Prefer coherent batches over one-file-at-a-time random edits.
4. Keep the application runnable.
5. Do not overwrite working features merely because rewriting is easier.
6. Search the repository before creating a duplicate component/helper/table.
7. After schema changes, search for outdated table/column references.
8. After PHP changes, run syntax checks where possible.
9. Never leave knowingly broken links, forms, routes, imports, asset paths, or SQL references.
10. Do not rely on client-side validation for financial/security rules.
11. Do not make balance changes without an auditable service/transaction flow.
12. Do not fabricate provider responses, regulatory approvals, banking licenses, or network partnerships.
13. Do not place secrets in source control.
14. Do not log full card numbers, passwords, OTPs, reset tokens, or provider secrets.
15. Prefer migrations over destructive database resets.
16. When replacing dangerous legacy behavior, document why.
17. Do not waste tokens repeatedly explaining plans. Inspect, implement, verify, summarize.
18. Keep comments useful; avoid comment spam.
19. Keep naming consistent around “Valtoria Bank,” “Valtoria,” customer, card, funding, transfer, credit, and transaction concepts.
20. Preserve a polished user experience even for errors, empty states, rejected applications, failed funding, and pending reviews.

---

# 29. Start Command

Begin with **MODULE 1 only**.

Analyze the repository currently open in the workspace. Do not immediately perform a mass rewrite.

First:

1. inspect the repository;
2. inspect the database/schema;
3. inventory existing Nexus functionality;
4. identify reusable vs unsafe components;
5. create `PROJECT_AUDIT.md`;
6. propose the target Valtoria architecture inside that audit;
7. then implement Module 1 in coherent batches.

At the end of Module 1, run available checks and give me the Module 1 checkpoint summary described above.

**Do not start Module 2 until I explicitly tell you to continue.**
