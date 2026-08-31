# Connect onboarding in Embiken

Status: open
Type: grilling
Blocked by: 01

## Question

Given Stripe’s facts from [Stripe Connect Express facts for Book](01-stripe-connect-express-facts.md), where does **Embiken** put Express onboarding and the Book pay gate?

Settled: new Express account per Tenant shop; no Standard attach; Book pay hidden until the account can charge except `$0`/`none` confirm; 3% application fee; quote lives in Embiken; Filament never checkouts. Platform User **may** view Express status and retry Account Link ([Platform User admin envelope](05-platform-user-admin.md)). Still lock whether a Manager also may, and the return/refresh URLs.

Lock:

- Account Link return and refresh URLs — Platform Filament vs Shop Filament vs both
- Who may restart onboarding (platform User, Manager, both)
- Which Stripe flag(s) disable Book pay
- Where Express status is shown
- Whether `account.updated` (and friends) is the only path that flips the gate, or also a refresh button

Research (do not re-litigate Stripe): destination charges; gate Book pay on `charges_enabled` (connected account also needs `transfers` active), not `details_submitted` or `payouts_enabled`; `return_url` ≠ can charge; omit `application_fee_amount` on a $0 PaymentIntent (Stripe rejects 0). **3% rounding is this ticket:** Stripe has no rule for in-house percent → integer cents (Pricing Tool “nearest cent” is the only official example). Pick floor / nearest / ceil here.

Do not re-open Express vs Standard. Record amendments with `record-rule`.
