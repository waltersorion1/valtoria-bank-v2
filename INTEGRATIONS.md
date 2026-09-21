# Provider integrations

Module 2 introduces `CardFundingProvider` and `SandboxCardFundingProvider`. `FINANCIAL_PROVIDER_MODE=sandbox` is the only implemented mode. Sandbox funding is visibly labeled, uses synthetic `sbox_pm_*` payment-method tokens and `SBOX-*` references, and does not contact or claim authorization from Visa, Mastercard, or a processor.

A live adapter must not be enabled until provider documentation and credentials are supplied. It must tokenize card data in provider-hosted fields, support 3DS/SCA where required, map provider states without auto-completing unknown outcomes, verify webhook signatures, reject replayed provider event references, and redact payloads. The application must never receive or store CVV and should not receive full PAN.

No live transfer rail, KYC vendor, SMS service, credit bureau, or document-storage provider is configured.
