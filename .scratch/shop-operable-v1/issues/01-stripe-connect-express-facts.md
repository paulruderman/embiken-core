# Stripe Connect Express facts for Book

Status: resolved
Type: research

## Question

What does Stripe actually require for **Connect Express** destination charges that match Embiken’s recorded money law: new Express account per shop, Account Link onboarding, Book PaymentIntent that **captures** the package confirm-threshold amount immediately, `application_fee_amount` = 3% of that charge, no Stripe Products/Prices for rentals, no Standard attach?

Need from primary sources (Stripe docs / API reference), not blogs:

- Account Link `return_url` / `refresh_url` — what they must be, what happens on each
- Which account flags mean “can charge” (`charges_enabled`, `payouts_enabled`, capabilities, `requirements`) for disabling Book pay until Express can charge
- Webhook events we must handle for onboarding and for a captured PaymentIntent plus refunds (`account.updated`, `payment_intent.*`, `charge.refunded`, …) and idempotency expectations
- Destination charge vs separate charges and transfers — which matches “charge on the platform, money to the connected account, application fee”
- Integer-cent rounding for a 3% fee (Stripe’s own rounding rules, if any)
- Test-mode vs live differences that would change the spec

Write findings to `.scratch/shop-operable-v1/research/stripe-connect-express.md` with citations. Do not decide Embiken product (those URLs and who restarts onboarding are [Connect onboarding in Embiken](10-connect-onboarding-in-embiken.md)).

## Answer

Destination charges match “charge on the platform, remainder to the connected account, application fee”: PaymentIntent with `transfer_data[destination]` + integer `application_fee_amount`, default `automatic_async` capture (not a hold). Create a new Express (or Express-equivalent controller) Account per shop — do not OAuth-attach an existing Standard account (type is immutable; Standard is documented as direct-only). Account Link `return_url` is “left the flow,” not success; `refresh_url` must mint a new single-use link (HTTPS in live). Gate Book pay on `charges_enabled` (destination also needs `transfers` active), not `details_submitted` or `payouts_enabled`. Destination PI events hit the platform webhook; `account.updated` hits the Connect webhook; dedupe by `event.id`. Stripe does not compute or round 3% for you.

Findings: [stripe-connect-express.md](../research/stripe-connect-express.md)
