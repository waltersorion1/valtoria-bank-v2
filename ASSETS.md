# Public-site asset delivery plan

Place the final files at the exact paths below, relative to the project root:

`C:\xampp\htdocs\valtoria-bank\assets\images\`

The application currently uses CSS compositions and text fallbacks, so missing marketing images do not break page layout. Do not place customer card details, identity documents, account screenshots, or other personal information in this public directory.

## Required brand assets

| Status | Exact project path | Recommended specification | Intended use | Variant / fallback |
|---|---|---|---|---|
| Available | `assets/images/logo.png` | PNG, 1672×941, transparent horizontal artwork | Public header and light authentication surfaces | Text/CSS fallback if unavailable |
| Available | `assets/images/logo-light.png` | PNG, 1672×941, transparent horizontal artwork | Navy footer, customer/admin sidebars, authentication story panels | White text/CSS fallback if unavailable |
| Available | `assets/images/logo-mark.png` | PNG, 1254×1254, transparent square artwork | Compact marks, receipts, and component fallbacks | CSS V mark fallback |
| Missing | `assets/images/brand/favicon.ico` | ICO containing 16×16, 32×32, and 48×48 | Browser tabs and bookmarks | Browser default icon |
| Available | `assets/images/brand/favicon.svg` | SVG, 1080×1080 | Modern-browser favicon across public, authentication, customer, and admin pages | `favicon.ico` fallback when later supplied |
| Missing | `assets/images/brand/apple-touch-icon.png` | PNG, 180×180 | iOS home-screen bookmark | No touch icon |
| Missing | `assets/images/brand/social-share.png` | PNG or WebP, 1200×630 | Open Graph/social preview | Text-only link preview |

The inherited `Logo-color.png`, `Logo-color-1.png`, and `LOGO-RECEIPT.jpg` files are legacy raster logos. Active branding now uses the lowercase PNG paths above; remove remaining legacy files only after confirming no retired template still depends on them.

## Required marketing imagery

| Priority | Exact project path | Recommended specification | Page / placement | Art direction and fallback |
|---|---|---|---|---|
| Available / in use | `assets/images/marketing/home-hero.webp` | WebP, 1600×1200, 4:3, ≤300 KB | Home-page hero | Premium card/account workspace scene with clear negative space |
| Available / in use | `assets/images/marketing/accounts-hero.webp` | WebP, 1600×1000, 8:5, ≤260 KB | Accounts hero/supporting panel | Calm dashboard/account overview without real customer data |
| High | `assets/images/marketing/cards-hero.webp` | WebP, 1600×1000, 8:5, ≤260 KB | Cards page | Unbranded partner-card arrangement; no readable PAN or security code; proof panel fallback |
| High | `assets/images/marketing/transfers-hero.webp` | WebP, 1600×1000, 8:5, ≤260 KB | Transfers page | Abstract person-to-person movement or connected accounts; proof panel fallback |
| High | `assets/images/marketing/funding-hero.webp` | WebP, 1600×1000, 8:5, ≤260 KB | Card Funding page | Compatible-card funding concept without third-party endorsement; proof panel fallback |
| High | `assets/images/marketing/credit-hero.webp` | WebP, 1600×1000, 8:5, ≤260 KB | Credit page | Planning/approved-terms concept, not cash imagery or guaranteed approval messaging; proof panel fallback |
| High | `assets/images/marketing/security-illustration.webp` | WebP or SVG, 1200×900, 4:3 | Security page | Account shield, access, review, and audit concept; dark CSS status panel fallback |
| Medium | `assets/images/marketing/help-hero.webp` | WebP, 1400×900, approximately 14:9 | Help page | Professional support/customer guidance with no visible sensitive data; proof panel fallback |
| Medium | `assets/images/marketing/about-office.webp` | WebP, 1600×1067, 3:2 | About page | Authentic team/office image with documented usage rights; neutral surface fallback |
| Low | `assets/images/marketing/contact-support.webp` | WebP, 1200×900, 4:3 | Contact page | Support professional or abstract communication scene; form-only fallback |

Provide a visually consistent set: restrained navy/blue palette, natural lighting, no crypto imagery, no piles of cash, no fake banking interfaces, and no visible card or identity information. Images should remain legible when cropped with `object-fit: cover`.

## Product and interface support assets

| Status | Exact project path | Recommended specification | Intended use | Fallback |
|---|---|---|---|---|
| Missing | `assets/images/placeholders/avatar-default.webp` | WebP, 256×256, square, ≤35 KB | Customer profile without an uploaded avatar | Initials/CSS circle |
| Missing | `assets/images/placeholders/article.webp` | WebP, 1200×675, 16:9 | Help/blog article without artwork | Neutral background block |
| Missing | `assets/icons/sprite.svg` | SVG symbol sprite using one consistent outline family | Public and authenticated navigation icons | Current letter marks and text labels |
| Missing | `assets/icons/status-success.svg` | SVG, 24×24 | Completed/verified status illustration | CSS badge color and text |
| Missing | `assets/icons/status-review.svg` | SVG, 24×24 | Pending/processing status illustration | CSS badge color and text |
| Missing | `assets/icons/status-failed.svg` | SVG, 24×24 | Failed/rejected status illustration | CSS badge color and text |

Icons must use `currentColor`, include a square viewBox, and remain understandable at 20–24 px. Do not introduce a second icon style or hotlink an icon CDN.

## Missing images referenced by the legacy blog page

The current `blog.php` references files that do not exist. If that page is retained before its redesign, provide:

| Exact project path | Recommended specification | Existing placement |
|---|---|---|
| `assets/images/secure-banking.jpg` | JPEG/WebP source, 900×600, 3:2 | “Secure Banking” card |
| `assets/images/fast-transactions.jpg` | 900×600, 3:2 | “Fast Transactions” card |
| `assets/images/customer-support.jpg` | 900×600, 3:2 | Customer support card |
| `assets/images/mobile-banking.jpg` | 900×600, 3:2 | Mobile banking card |
| `assets/images/loan-options.jpg` | 900×600, 3:2 | Credit/loan card |
| `assets/images/investment-planning.jpg` | 900×600, 3:2 | Investment-planning card |

These are legacy references, not recommended final filenames. The preferred long-term approach is to migrate the page to `assets/images/marketing/` and WebP.

## Delivery requirements

- Export photographs in sRGB and remove embedded location/author metadata.
- Supply original licensed source files separately; only optimized production exports belong in the repository.
- Keep hero images below roughly 300 KB and supporting images below roughly 180 KB when quality permits.
- Do not bake headings, buttons, logos, card numbers, or legal wording into images.
- Provide meaningful alt-text guidance with each delivered image; decorative assets should use empty alt text.
- Light and dark logo files must share the same dimensions so layout does not shift.
- Filenames are case-sensitive in production. Use the exact lowercase paths above.
