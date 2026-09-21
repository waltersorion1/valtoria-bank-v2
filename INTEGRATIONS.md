# Integrations and manual operations

Valtoria currently uses a manual partner-operations model. No external card, transfer, KYC, credit-bureau, or payment API is called by the application.

## Card funding

`ManualCardFundingProvider` creates a pending request and an internal reference. It does not claim an external authorization or charge. Operations must move the request from `pending` to `processing`, then to `completed` or `failed`. Customer balance and balanced ledger entries are posted only at completion.

## Transfers

Customer transfer submissions remain pending. Operations review them in the same two-stage queue. Available balance is rechecked and ledger entries are posted atomically when completion is confirmed.

## Card compatibility

The application accepts safe card metadata and an approved partner reference. It never accepts or stores a full PAN or CVV. Administrators approve or reject compatibility using masked metadata and the partner's approved out-of-band process.

Before adding a card-data intake channel, obtain a reviewed PCI DSS architecture, document roles and retention, and use a compliant partner-controlled or isolated channel.
