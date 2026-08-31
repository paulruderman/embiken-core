# Stripe Connect Express destination charges

Primary-source facts for Embiken’s recorded money law: new Express (or Express-equivalent) account per shop, Account Link onboarding, PaymentIntent that captures immediately, `application_fee_amount` = 3% of that charge, no Stripe Products/Prices for rentals, no Standard attach.

This note does **not** decide Embiken product (return/refresh URLs in the app, who restarts onboarding). Those are [Connect onboarding in Embiken](../issues/10-connect-onboarding-in-embiken.md).

Sources are Stripe docs and API reference only. Retrieved 2026-08-31.

---

## Verdict

Stripe’s **destination charge** is the charge type that matches “charge on the platform, remaining money to the connected account, platform takes an application fee.” Create a PaymentIntent on the platform with `transfer_data[destination]` and `application_fee_amount`. That is **not** separate charges and transfers, and **not** a direct charge.

A **new connected account created by the platform** (legacy `type=express`, or v1 `controller` properties that map to Express: Stripe Dashboard type `express`, Stripe collects requirements, platform pays fees and takes loss liability) is how you get an Express-style shop account. **OAuth “Connect with Stripe” attaches an existing Standard account.** Standard accounts are documented as **direct charges only**. Account type / dashboard type is **immutable**; changing it means a **new Account object**.

Account Link `return_url` / `refresh_url` are **required** strings. They are not a success signal. `return_url` means the flow was entered and left (including “Save for later”). `refresh_url` means the link is expired, already visited, or otherwise invalid — your server should mint a **new** Account Link and redirect. Live mode requires **HTTPS** for those URLs. The Account Link `url` is **single-use** and expires in **a few minutes** (`expires_at` on the object; the API example is 300 seconds).

“Can charge” is **`charges_enabled`**, not `details_submitted` and not `payouts_enabled`. `charges_enabled` is whether the account can process charges. For destination charges the connected account needs the **`transfers`** capability active. `payouts_enabled` is whether funds can be paid out to the bank; Stripe says you may let them transact before payouts are enabled. After `return_url`, retrieve the Account (or listen to `account.updated`) and inspect `charges_enabled` and `requirements`.

Immediate capture is **`capture_method` `automatic` or the default `automatic_async`**, not `manual` (a hold). `application_fee_amount` is an **integer in the charge’s minor unit**, must be **positive and less than** the charge, and is **capped at the captured amount**. Stripe does **not** compute “3%” for you when you pass that parameter. Stripe does **not** document a rounding rule for a percentage you compute yourself; the only official percentage-rounding example is the Platform Pricing Tool (nearest cent). A PaymentIntent **amount** is also an integer minor-unit value; USD minimum is **$0.50**. Products and Prices are **not** required: destination-charge PaymentIntents take `amount` + `currency`.

Destination-charge PaymentIntent events fire on the **platform** webhook (not the Connect/`event.account` webhook). `account.updated` fires on the **connected-accounts** webhook. Deduplicate webhooks by **`event.id`**. Idempotent API POSTs use the **`Idempotency-Key`** header (kept ≥24 hours).

---

## 1. Destination charge vs separate charges and transfers

Connect has three charge types: **direct**, **destination**, and **separate charges and transfers** (SCT). Direct is a charge on the connected account. Destination and SCT are **indirect**: the payment is created on the **platform**, then funds move to the connected account. ([Understand how charges work](https://docs.stripe.com/connect/charges))

**Destination charges** match “charge on the platform, money to the connected account, application fee”:

- You create the charge **on the platform’s account**. ([Create destination charges](https://docs.stripe.com/connect/destination-charges))
- Funds **immediately transfer** to the connected account after capture. You decide whether some or all of those funds transfer, and whether to take an application fee. ([Understand how charges work](https://docs.stripe.com/connect/charges); [Create destination charges](https://docs.stripe.com/connect/destination-charges))
- `transfer_data[destination]` **is** what marks a destination charge: “the charge is processed on the platform and then the funds are immediately and automatically transferred to the connected account’s pending balance.” ([Create destination charges](https://docs.stripe.com/connect/destination-charges), parameter table)
- With `application_fee_amount`, “the full charge amount is immediately transferred from the platform to the `transfer_data[destination]` account after the charge is captured. The `application_fee_amount` (capped at the full amount of the charge) is then transferred back to the platform.” Stripe fees are deducted from the **platform**. ([Create destination charges](https://docs.stripe.com/connect/destination-charges); [Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))
- Example flow: 10.00 USD charge → connected account pending 10.00 → 1.23 USD application fee back to platform → Stripe fee (e.g. 0.59 USD) from platform → platform keeps 0.64 USD net. ([Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))
- Stripe **recommends destination charges** for connected accounts that do **not** have the full Stripe Dashboard. ([Create destination charges](https://docs.stripe.com/connect/destination-charges))
- Refunds and chargebacks debit the **platform** balance. You reverse the transfer (or set `reverse_transfer=true` on the refund) to recover from the connected account. ([Understand how charges work](https://docs.stripe.com/connect/charges); [Handle refunds and disputes](https://docs.stripe.com/connect/marketplace/tasks/refunds-disputes))

**Separate charges and transfers** do **not** match that sentence as tightly:

- You charge the platform **first**, then create a **separate Transfer**. Charge and transfer are decoupled. ([Understand how charges work](https://docs.stripe.com/connect/charges))
- Use when one payment must split to **multiple** connected accounts, the destination is **unknown at charge time**, or you need to transfer before/without a matching charge. Stripe: “Use them only if your business use case requires them.” ([Understand how charges work](https://docs.stripe.com/connect/charges))
- Application fee is taken by transferring **less** than the charge (or via funds-segregation `application_fee_amount` on the Transfer, private preview). ([Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))

**Direct charges** put the charge on the connected account. Stripe says they are **not recommended for legacy v1 Express and Custom**; use destination charges instead. Direct also requires `card_payments` active. ([Understand how charges work](https://docs.stripe.com/connect/charges))

**`on_behalf_of`**: optional on destination charges. Sets the connected account as settlement merchant (statement descriptor, address, settlement country/fees). Cross-region destination charges often **require** it. Without it, destination charges use the **platform’s** payment method configuration and branding. ([Understand how charges work](https://docs.stripe.com/connect/charges); [Create destination charges](https://docs.stripe.com/connect/destination-charges))

**Capability for destination charges:** the connected account needs **`transfers`**. “Payments using the `transfers` capability include Destination charges and Separate charges and transfers.” `card_payments` is for charge types where the connected account is merchant of record. ([Account capabilities](https://docs.stripe.com/connect/account-capabilities))

Stripe’s Express examples still request **both** `card_payments` and `transfers` when creating the account. If you omit `capabilities`, Connect Onboarding uses Dashboard Configuration settings for the account’s country. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts))

---

## 2. New Express account per shop — not Standard attach

### Create, don’t OAuth-attach

Express accounts are created with the Accounts API: `type=express`, or the controller equivalent:

```
controller[fees][payer]=application
controller[losses][payments]=application
controller[stripe_dashboard][type]=express
```

(`requirement_collection` is `stripe` on Express.) ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding); [Create an account](https://docs.stripe.com/api/accounts/create); [Migrate to controller properties](https://docs.stripe.com/connect/migrate-to-controller-properties))

The `type` parameter is **deprecated**. Stripe tells new platforms to use **controller properties** (or Accounts v2). Creating with `type=express` still maps to Express; it sets `controller.fees.payer` to `application_express` rather than `application` (a Direct-charge fee-billing difference). ([Create an account](https://docs.stripe.com/api/accounts/create); [Migrate to controller properties](https://docs.stripe.com/connect/migrate-to-controller-properties))

**You cannot convert an existing account’s type.** “After you create a connected account, you can’t change its type.” Dashboard type is also permanent: “To change a connected account’s dashboard, you must create a new `Account` object.” Country is also immutable for Express. ([Connected account types](https://docs.stripe.com/connect/accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding); [Using Express connected accounts](https://docs.stripe.com/connect/express-accounts))

### What “Standard attach” actually is

**OAuth** (`GET https://connect.stripe.com/oauth/authorize`) is the flow that lets a user **connect an existing Stripe account** (or create a new Standard one) to the platform. Stripe: “The process of creating a Stripe account is incorporated into our authorization flow. You don’t need to worry about whether or not your users already have accounts.” After they connect “their existing or newly created account,” you exchange the code for `stripe_user_id`. ([Using OAuth with Standard accounts](https://docs.stripe.com/connect/oauth-standard-accounts); [Connect OAuth reference](https://docs.stripe.com/connect/oauth-reference))

That is a **Standard** connection (`type=standard` / full Dashboard). Stripe’s Standard onboarding docs still create Standard accounts with `type=standard` + Account Links as the **recommended** method for Standard; OAuth remains for “an extension or an application that needs access to an **existing** account.” ([Using Standard connected accounts](https://docs.stripe.com/connect/oauth) — page title “Using Standard connected accounts”)

**Standard supported charge types are listed as Direct only.** Express supports destination, SCT, and direct. ([Connected account types](https://docs.stripe.com/connect/accounts) comparison table)

So attaching an existing full Stripe account via OAuth does **not** give you an Express destination-charge account. You cannot later flip it to Express. A new Account object is required for Express (or Express-equivalent controller properties).

OAuth deauthorize is documented as: “You can only revoke a Standard account’s access to your platform.” ([Connect OAuth reference](https://docs.stripe.com/connect/oauth-reference))

### Express vs Standard liability (destination)

On destination charges, fraud/dispute liability is on the **platform** for both Standard and Express in Stripe’s comparison table. For Express, “your platform is responsible for losses incurred by Express connected accounts” and you must vet for fraud. ([Connected account types](https://docs.stripe.com/connect/accounts); [Using Express connected accounts](https://docs.stripe.com/connect/express-accounts))

---

## 3. Account Link `return_url` / `refresh_url`

### Required parameters

Create Account Link with: `account`, `refresh_url`, `return_url`, `type=account_onboarding`. Both URLs are **required** strings. Optional `collection_options.fields`: `currently_due` (incremental) or `eventually_due` (up-front). ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Create an account link](https://docs.stripe.com/api/account_links/create); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

**`type=account_update` is not available for Express.** You can create `account_update` links only for accounts where the platform collects requirements (Custom). “You can’t create them for accounts that have access to a Stripe-hosted Dashboard.” Express has the Express Dashboard. Later requirement updates for Express go through **`account_onboarding`** again (or Express Dashboard / embedded components). ([Create an account link](https://docs.stripe.com/api/account_links/create); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

### What the URLs must be

Stripe does **not** prescribe a path or query string. They must be URLs your platform serves:

- **Test / localhost:** HTTP is allowed (e.g. `http://localhost`).
- **Live mode: HTTPS only.** “You must update testing URLs to HTTPS URLs before you go live.” ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

No state is passed on either redirect. You do not get `success=true` or an account id in the query string. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

**Live mode extra:** you must set Connect **site links** (payments and connected-account workflow URLs) in the Dashboard **before** creating an Account Link or Account Session in live mode. Testing environments use the same URLs as live. ([Build a full embedded integration](https://docs.stripe.com/connect/build-full-embedded-integration) — “You must set these links before creating an `AccountSession` or an `AccountLink` in live mode.”)

Connect Onboarding also requires platform branding (name, color, icon) in Connect settings. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

### `return_url` — what happens

Stripe redirects here when the connected account **completes the flow or leaves it** (hosted onboarding also: “or click Save for later at any point”).

This **does not** mean:

- all information has been collected, or
- there are no outstanding requirements.

It only means “the flow was entered and exited properly.” ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding); API: “The URL that the user will be redirected to upon leaving or completing the linked flow.” [Create an account link](https://docs.stripe.com/api/account_links/create))

After `return_url`, Stripe says: retrieve the Account and check **`details_submitted`** and/or **`charges_enabled`**, **or** listen to **`account.updated`**. A user on `return_url` might still be incomplete; generate a **new** Account Link if they need to continue. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

Hosted onboarding: “Retrieve the account and check the requirements hash for outstanding requirements. Alternatively, listen to the `account.updated` event … and cache the state of the account.” ([Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

### `refresh_url` — what happens

Redirected here when:

- The link **expired** (“a few minutes passed after the link was created”).
- They **already visited** the URL (refresh, back, forward).
- The link was **shared** (e.g. a messenger previews the URL and burns the single-use link). ([Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding) extra case)
- The platform can **no longer access** the account.
- The account has been **rejected**. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

API: “The URL you specify should attempt to generate a new account link with the **same parameters**, then redirect the user to the new account link’s URL. If a new account link cannot be generated or the redirect fails you should display a useful error to the user.” ([Create an account link](https://docs.stripe.com/api/account_links/create))

### Single-use and expiry

- Each Account Link `url` “can only be used once because it grants access to the account holder’s personal information.” Authenticate the user in **your** app before redirecting. **Do not** email/text/send the URL outside the application. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))
- Object fields: `created`, `expires_at`, `url`. Example: `created` 1680577733, `expires_at` 1680578033 (**300 seconds**). Stripe prose says “a few minutes,” not a guaranteed 5 minutes — trust `expires_at`. ([The Account Link object](https://docs.stripe.com/api/account_links/object); [Create an account link](https://docs.stripe.com/api/account_links/create))

### After the first Account Link (Express KYC lock)

“Before creating the first account link for an Express connected account, prefill any Know Your Customer (KYC) information. After you create an account link for an Express connected account, you can’t read or update its KYC information.” Same for Standard. Hosted onboarding: “Prefill any account information before generating the Account Link because you can’t read or write information for the connected account afterward” when `controller.requirement_collection` is `stripe`. ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

Hosted onboarding is **web browsers only**, not embedded web views. ([Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

---

## 4. Which flags mean “can charge”

### The boolean that matches “can process charges”

| Field | Meaning | Use for “disable pay until Express can charge”? |
| --- | --- | --- |
| `charges_enabled` | “Whether the account can process charges.” | **Yes.** Express onboarding: after `return_url`, “check for `charges_enabled`.” Embedded: “A connected account is ready to receive payments when its `charges_enabled` property is true.” |
| `payouts_enabled` | “Whether the funds in this account can be paid out.” | **Not required to take a charge.** “Depending on your risk policies, you can allow your connected account to start transacting without payouts enabled. You must eventually enable payouts.” |
| `details_submitted` | “Whether account details have been submitted.” Accounts with Dashboard access “cannot receive payouts before this is true.” `false` → send them back through onboarding. | **Form completed, not “can charge.”** Verification can still be pending; `charges_enabled` can still be false. |
| `capabilities[transfers]` / `capabilities[card_payments]` | `active`, `inactive`, or `pending`. | Destination charges need **`transfers` `active`**. `charges_enabled` “evaluates if either `card_payments` or `transfers` capabilities are active.” |
| `requirements.*` | Why charges/payouts are off and what is due. | Inspect when `charges_enabled` is false. Do not treat empty `currently_due` alone as “can charge.” |

Sources: [The Account object](https://docs.stripe.com/api/accounts/object) (`charges_enabled`, `payouts_enabled`, `details_submitted`, `capabilities`, `requirements`); [Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Handle verification with the API](https://docs.stripe.com/connect/handling-api-verification); [API onboarding](https://docs.stripe.com/connect/api-onboarding) (“charges_enabled confirms that your full charge path including the charge and transfer works correctly…”); [Build a full embedded integration](https://docs.stripe.com/connect/build-full-embedded-integration); [Account capabilities](https://docs.stripe.com/connect/account-capabilities).

Stripe explicitly: “You can’t confirm the integration based on a single value because statuses can vary depending on the application and related policies.” Checking **both** `charges_enabled` and `payouts_enabled` is how they define “compliant and operational”; for **taking a Book payment**, their own onboarding copy points at **`charges_enabled`**. ([API onboarding](https://docs.stripe.com/connect/api-onboarding); [Using Express connected accounts](https://docs.stripe.com/connect/express-accounts))

### `requirements` hash (when charges are off)

If `charges_enabled` or `payouts_enabled` is false, read `requirements`:

| Property | Meaning |
| --- | --- |
| `currently_due` | Must resolve by `current_deadline` for the account to remain active. Non-empty ⇒ outstanding requirements that **might restrict** capabilities. |
| `current_deadline` | Unix time; missing this typically **disables payouts**; Stripe **might also disable charges** if payouts are already off and the account is unresponsive. |
| `past_due` | Subset of `currently_due`; capabilities already disabled because the deadline was missed. |
| `eventually_due` | Will become required at thresholds (volume, etc.). Up-front onboarding collects these via Account Link `collection_options.fields=eventually_due`. |
| `pending_verification` | Stripe is reviewing; `disabled_reason` may be `requirements.pending_verification` — “No action is required.” |
| `disabled_reason` | Why charges/transfers are disabled. Values include `requirements.past_due`, `requirements.pending_verification`, `under_review`, `listed`, `rejected.fraud`, `rejected.incomplete_verification`, `rejected.listed`, `rejected.other`, `rejected.terms_of_service`, `action_required.requested_capabilities`. Platform pause uses `platform_paused`. |
| `errors` | Validation/verification failures on specific fields. |

([Handle verification with the API](https://docs.stripe.com/connect/handling-api-verification); [Pausing payments or payouts](https://docs.stripe.com/connect/pausing-payments-or-payouts-on-connected-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

Hosted onboarding: send them back through onboarding if `currently_due` **or** `eventually_due` is non-empty; you do not need to name the fields — the form knows. If `currently_due` is still present when `current_deadline` arrives, functionality is disabled and those fields move to `past_due`. Listen to **`account.updated`**. ([Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding))

Capability `status` can leave `active` later if a future verification fails. Listen to `account.updated` (and `capability.updated`). ([API onboarding](https://docs.stripe.com/connect/api-onboarding); [Types of events](https://docs.stripe.com/api/events/types) — `capability.updated`: “Occurs whenever a capability has new requirements or a new status.”)

---

## 5. PaymentIntent: capture immediately + 3% application fee

### Destination PaymentIntent (no Product/Price)

Server creates a PaymentIntent with `amount`, `currency`, `application_fee_amount`, and `transfer_data[destination]`:

```
POST /v1/payment_intents
amount=1000
currency=usd
application_fee_amount=123
transfer_data[destination]={{CONNECTED_ACCOUNT_ID}}
```

([Create destination charges](https://docs.stripe.com/connect/destination-charges?platform=web&ui=elements) — “Create a PaymentIntent”)

There is **no** `product`, `price`, or Checkout `line_items` on this path. Checkout destination charges **can** use ad-hoc `price_data` / `product_data` without a catalog Product either. ([Create destination charges](https://docs.stripe.com/connect/destination-charges))

`amount` is a **positive integer in the smallest currency unit**. USD example: `1000` = $10.00. API: “The minimum amount is $0.50 US or equivalent in charge currency.” ([Create a PaymentIntent](https://docs.stripe.com/api/payment_intents/create); [Supported currencies](https://docs.stripe.com/currencies) — USD minimum 0.50)

### Capture vs hold

`capture_method`:

- **`automatic`**: “Stripe automatically captures funds when the customer authorizes the payment.”
- **`automatic_async`** (**default**): same capture-on-authorize, asynchronous; Stripe recommends it over `automatic` for latency.
- **`manual`**: “Place a hold on the funds … don’t capture the funds until later.”

([Create a PaymentIntent](https://docs.stripe.com/api/payment_intents/create); [The PaymentIntent object](https://docs.stripe.com/api/payment_intents/object))

Destination-charge examples **omit** `capture_method`, so they get **`automatic_async`**: capture on authorization, **not** a card hold. Transfer of funds to the connected account happens **after the charge is captured**. ([Create destination charges](https://docs.stripe.com/connect/destination-charges); [Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))

`payment_intent.succeeded` is “when a PaymentIntent has successfully completed payment.” `payment_intent.amount_capturable_updated` is for **manual** capture (`amount_capturable`). `charge.captured` is “whenever a previously uncaptured charge is captured.” ([Types of events](https://docs.stripe.com/api/events/types))

### `application_fee_amount`

- Integer, optional, **same currency as the charge**. ([Create a PaymentIntent](https://docs.stripe.com/api/payment_intents/create); [Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))
- “Must be **positive** and **less than** the amount of the charge.” Collected amount is **capped at the captured amount**. ([Collect application fees (SaaS)](https://docs.stripe.com/connect/saas/tasks/app-fees))
- Marketplace destination-charge docs also say it is **capped at the total transaction amount**. ([Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))
- No extra Stripe fee on the application fee itself; **platform pays Stripe fees** on destination charges. ([Collect application fees (SaaS)](https://docs.stripe.com/connect/saas/tasks/app-fees); [Understand how charges work](https://docs.stripe.com/connect/charges))
- Explicit `application_fee_amount` **overrides** Platform Pricing Tool rules. ([Create destination charges](https://docs.stripe.com/connect/destination-charges); [Define a custom pricing strategy](https://docs.stripe.com/connect/platform-pricing-tools/pricing-schemes))
- Alternative: `transfer_data[amount]` = integer to send to the connected account (you subtract the fee yourself). Stripe prefers `application_fee_amount` for reporting (creates an ApplicationFee object). ([Create destination charges](https://docs.stripe.com/connect/destination-charges))

**Implications for a 3% fee and for $0 / `none` confirm:**

- Stripe will not accept `application_fee_amount=0` (must be **positive**). Omit the parameter if the platform takes no fee on that PaymentIntent.
- A PaymentIntent `amount` of 0 is below the **$0.50 USD** (or equivalent) minimum for ordinary charges. Subscriptions can be zero for coupons/trials; “any non-zero amount is still subject to the applicable minimum.” ([Supported currencies](https://docs.stripe.com/currencies))
- 3% of a $0.50 charge is 1.5 cents — see rounding below.

### Refunds of destination charges

- Refunds debit the **platform**. Default: connected account **keeps** the transfer; platform covers it. Set **`reverse_transfer=true`** to pull funds back. Partial refunds reverse a **proportional** transfer. ([Handle refunds and disputes](https://docs.stripe.com/connect/marketplace/tasks/refunds-disputes))
- Application fee is **not** returned unless **`refund_application_fee=true`** (proportional to the payment refund). Example: 100 USD charge, 5 USD fee, refund 40 USD → 2 USD fee refund. ([Handle refunds and disputes](https://docs.stripe.com/connect/marketplace/tasks/refunds-disputes))
- Insufficient platform balance → refund `pending` until funds exist. If the refund also requests transfer reversal and the connected account has insufficient balance, the **refund request errors** instead of creating a pending refund. ([Understand how charges work](https://docs.stripe.com/connect/charges))

---

## 6. Integer-cent rounding for 3%

**Stripe does not document a rounding rule for “3% of this PaymentIntent.”** When you pass `application_fee_amount`, you pass an **already-rounded integer** in the charge’s minor unit. ([Create a PaymentIntent](https://docs.stripe.com/api/payment_intents/create); [Supported currencies](https://docs.stripe.com/currencies))

What Stripe **does** specify:

1. **Minor units, no decimals in the API.** Two-decimal currencies (USD): `1099` = 10.99 USD. Zero-decimal (JPY): `10` = 10 JPY. Special cases (ISK, UGX, HUF/TWD payouts) have extra divisibility rules. ([Supported currencies](https://docs.stripe.com/currencies))
2. **`application_fee_amount` is that same integer type**, same currency as the transaction, positive, less than charge, capped at captured amount. ([Collect application fees (SaaS)](https://docs.stripe.com/connect/saas/tasks/app-fees); [Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees))
3. **Platform Pricing Tool** (Dashboard percentage schemes, **not** in-house `application_fee_amount`): after markups/discounts, `14.8 × 1.04 × 0.97 = 14.93024`, “**rounding** to a total fee … of **14.93 USD**” — i.e. **nearest cent** in that example. Explicit PaymentIntent `application_fee_amount` **overrides** the scheme. ([Define a custom pricing strategy](https://docs.stripe.com/connect/platform-pricing-tools/pricing-schemes))
4. Invoice UGX: Stripe “automatically rounds that amount to the **nearest number evenly divisible by 100**.” That is invoice-specific, not application fees. ([Supported currencies](https://docs.stripe.com/currencies))

So: computing `floor(0.03 * amount)`, `round(0.03 * amount)`, or `ceil(0.03 * amount)` is **not specified** by Stripe for `application_fee_amount`. The only official percentage→money rounding example is Pricing Tool **nearest cent**. After you pick an integer, Stripe will reject it if it is not positive or not less than `amount`, and will cap at captured amount.

---

## 7. Webhooks and idempotency

### Two webhook scopes

Connect integrations need a webhook. Events have **two scopes**:

| Scope | How you subscribe | What you get for this design |
| --- | --- | --- |
| **Your account** (`connect=false` / “Events from: Your account”) | Platform endpoint | **Indirect** (destination + SCT) PaymentIntents/Charges on the platform; Customers on the platform. `payment_intent.succeeded` for destination charges is documented here with **no** `event.account`. |
| **Connected accounts** (`connect=true`) | Connect endpoint | `account.updated` for connected accounts; **direct** charges on those accounts (`event.account` = connected account id). |

([Connect webhooks](https://docs.stripe.com/connect/webhooks); [Create a webhook endpoint](https://docs.stripe.com/api/webhook_endpoints/create) — `connect` true = events from all connected accounts)

Destination-charge `payment_intent.succeeded` example is explicitly **“non-direct charge”**: handle the PaymentIntent on the platform; do **not** look for `event.account`. Direct-charge example **does** use `event.account`. ([Connect webhooks](https://docs.stripe.com/connect/webhooks))

Production webhook URLs receive **both live and test** events; development URLs receive **only test**. Check **`livemode`**. Sandbox accounts need **separate** endpoints. ([Connect webhooks](https://docs.stripe.com/connect/webhooks))

Registered endpoints must be publicly accessible **HTTPS**. ([Receive Stripe events](https://docs.stripe.com/webhooks))

### Onboarding

| Event | Object | Notes |
| --- | --- | --- |
| `account.updated` | `account` | “Occurs whenever an account status or property has changed.” Connect table: “monitor changes to connected account requirements and status.” **Available for all connected accounts.** This is the onboarding/status event. |
| `capability.updated` | `capability` | New requirements or new status. |
| `person.updated` | `person` | Persons API; Express/Custom (platform-controlled). |
| `account.external_account.updated` | bank/card | Can affect payouts; Express/Custom. |
| `account.application.deauthorized` | `application` | Disconnect; **Standard / Dashboard access**. Not the Express happy path. |

([Types of events](https://docs.stripe.com/api/events/types); [Connect webhooks](https://docs.stripe.com/connect/webhooks); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding); [Handle verification with the API](https://docs.stripe.com/connect/handling-api-verification))

Do **not** treat `return_url` as the source of truth; Stripe points at `account.updated` + Account retrieve.

### Captured PaymentIntent (destination, automatic capture)

Stripe Connect’s “most common” table lists **`payment_intent.succeeded`** for destination **and** direct. ([Connect webhooks](https://docs.stripe.com/connect/webhooks))

Also relevant:

| Event | When |
| --- | --- |
| `payment_intent.succeeded` | Payment completed (captured automatic path). |
| `payment_intent.payment_failed` | Failed to create payment method or payment. |
| `payment_intent.processing` | Started processing (delayed methods). |
| `payment_intent.canceled` | Canceled. |
| `payment_intent.requires_action` | Customer action needed (e.g. 3DS). |
| `charge.succeeded` | Charge successful. |
| `charge.updated` | Description/metadata, **or upon an asynchronous capture**. |
| `application_fee.created` | Application fee created on a charge. SaaS fees doc: listen for this when fees are created asynchronously (called out for **direct** charges; still the event for an ApplicationFee object). |

`payment_intent.amount_capturable_updated` / `charge.captured` apply if you used **`capture_method=manual`**, which this design does not.

Checkout-only events (`checkout.session.completed`, etc.) apply only if you use Checkout Sessions, not a raw PaymentIntent.

([Types of events](https://docs.stripe.com/api/events/types); [Collect application fees (SaaS)](https://docs.stripe.com/connect/saas/tasks/app-fees); [Create destination charges](https://docs.stripe.com/connect/destination-charges) Checkout variant)

### Refunds

Stripe refunds guide: “At a minimum, Stripe recommends that you listen for the **`refund.created`** event.” `charge.refunded` still fires (including partials) but “Listen to `refund.created` for information about the refund.”

| Event | Notes |
| --- | --- |
| `refund.created` | Refund created (recommended). |
| `refund.updated` | Metadata, ARN, etc. |
| `refund.failed` | Refund failed — arrange another way to repay. |
| `charge.refunded` | Charge refunded including partials. |
| `application_fee.refunded` | Fee refunded (via charge refund with `refund_application_fee` or direct fee refund), including partials. |
| `application_fee.refund.updated` | Fee refund object updated. |
| `charge.refund.updated` | **Deprecated**; use `refund.updated`. |

([Refund and cancel payments](https://docs.stripe.com/refunds); [Types of events](https://docs.stripe.com/api/events/types); [Changelog: refund webhook update](https://docs.stripe.com/changelog/acacia/2024-10-28/refund-webhook-update.md))

Disputes (platform-liable on destination): `charge.dispute.created` (and related). ([Handle refunds and disputes](https://docs.stripe.com/connect/marketplace/tasks/refunds-disputes))

### Idempotency — two layers

**Webhook deliveries** (Stripe may send the same Event more than once):

- Log **`event.id`** and skip already-processed ids. Do **not** use `created` for ordering or dedupe (second resolution). ([Receive Stripe events](https://docs.stripe.com/webhooks) — “Handle duplicate events”; “Track event IDs to identify duplicate deliveries”)
- Sometimes **two Event objects** represent the same logical change: dedupe with **`data.object.id` + `event.type`**. ([Receive Stripe events](https://docs.stripe.com/webhooks))
- Live mode: retry **up to three days**, exponential backoff. Sandbox: **three** retries over a few hours. Return **2xx** quickly; do long work async. Verify signatures. ([Receive Stripe events](https://docs.stripe.com/webhooks); [Connect webhooks](https://docs.stripe.com/connect/webhooks))

**API writes** (your PaymentIntent / Refund / Account Link creates):

- Send **`Idempotency-Key`** on **POST**. Stripe stores first status+body ≥ **24 hours**; same key + same params replay that result (including 500s). Different params with the same key error. Keys up to 255 characters; Stripe suggests UUIDv4. **GET/DELETE**: keys have no effect. ([Idempotent requests](https://docs.stripe.com/api/idempotent_requests))

OAuth token exchange is **not** idempotent: consuming an authorization code twice **revokes** the connection. Irrelevant if you do not use Standard OAuth. ([Connect OAuth reference](https://docs.stripe.com/connect/oauth-reference))

---

## 8. Test mode vs live (spec-changing)

| Topic | Test / sandbox | Live |
| --- | --- | --- |
| Account Link `return_url` / `refresh_url` | HTTP and localhost allowed | **HTTPS only** |
| Connect site links | Same URL values as live; not a live-mode create blocker in the same way | **Must** be set before creating Account Link / Account Session |
| Identity | Tokens (DOBs, `address_full_match`, `000000000` SSN, file tokens, `000-000` SMS). Immediate match tokens skip webhook delay | Real KYC; volume can add **eventually_due** requirements |
| Capabilities | “Sandboxes might **not enforce** some capabilities” — actions may succeed even if capability is not `active` | Enforced |
| Pause payments/payouts | Flags flip (`charges_enabled` false, `disabled_reason=platform_paused`) but sandbox **does not block** creating charges/payouts | Enforced |
| Test cards for extra requirements | Connect trigger cards work only with **`on_behalf_of` or a direct charge** on an account that has an `eventually_due` requirement | Real volume thresholds |
| Payouts | Simulated; Dashboard-access test accounts “always have payouts enabled” given a valid bank, “never requires real identity verification” | Real bank + verification |
| Webhooks | Dev URLs: **test only**. Sandbox retries: 3 times / few hours. Separate sandbox endpoints | Production URLs get **live and test**; retries **3 days**. Check `livemode` |
| API keys | `sk_test_` / `pk_test_` | `sk_live_` / `pk_live_`; OAuth `client_id` mode must match token exchange |
| Payment page | Elements: can test without HTTPS | Checkout page must be **https://** |

([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding); [Build a full embedded integration](https://docs.stripe.com/connect/build-full-embedded-integration); [Testing Stripe Connect](https://docs.stripe.com/connect/testing); [Account capabilities](https://docs.stripe.com/connect/account-capabilities); [Pausing payments or payouts](https://docs.stripe.com/connect/pausing-payments-or-payouts-on-connected-accounts); [Connect webhooks](https://docs.stripe.com/connect/webhooks); [Receive Stripe events](https://docs.stripe.com/webhooks); [Create destination charges](https://docs.stripe.com/connect/destination-charges?platform=web&ui=elements); [Connect OAuth reference](https://docs.stripe.com/connect/oauth-reference))

---

## 9. Stripe’s current API posture (do not treat as Embiken product)

Stripe pages for Express/Standard/Custom now carry a **deprecated-feature** banner: new Connect platforms are steered to **Accounts v2** or **v1 Accounts with controller properties**. `type=express` still exists and still documents Account Links + destination charges. Accounts v2 **cannot** be used with OAuth (listed limitation). ([Using Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Connected account types](https://docs.stripe.com/connect/accounts); [Connect and the Accounts v2 API](https://docs.stripe.com/connect/accounts-v2); [Integration recommendations](https://docs.stripe.com/connect/integration-recommendations))

Controller values that **map to Express behavior**: `losses.payments=application`, `fees.payer=application` (or `application_express` if created via `type=express`), `requirement_collection=stripe`, `stripe_dashboard.type=express`. ([Migrate to controller properties](https://docs.stripe.com/connect/migrate-to-controller-properties))

This note records that tension. It does not choose v1 Express vs v2 vs controller-only.

---

## Mapping to the ticket’s assumptions

| Assumption | Stripe’s actual requirement |
| --- | --- |
| New Express account per shop | Create a connected Account (`type=express` or Express controller properties). One Account per shop operator is a platform choice; Stripe requires **an** Account object you created, not a shared Standard login. |
| Account Link onboarding | Yes for Express-hosted onboarding: `type=account_onboarding`, required `return_url` + `refresh_url`. |
| PaymentIntent captures immediately | Default `automatic_async` (or `automatic`). Not `manual`. Transfer runs **after capture**. |
| `application_fee_amount` = 3% of the charge | You compute an integer minor-unit fee and pass it. Stripe does not apply 3% for you. Must be positive and `< amount`. |
| No Stripe Products/Prices for rentals | Valid: PaymentIntent `amount` + `currency` is enough. |
| No Standard attach | Valid for destination charges: do not OAuth-connect an existing full Stripe account. Type/dashboard immutable; Standard table lists **direct only**. |

---

## Gaps (Stripe does not specify)

- Exact rounding of an in-house **percentage** to `application_fee_amount` (half-up vs banker's vs floor). Only Pricing Tool “nearest cent” is shown.
- Exact Account Link TTL beyond `expires_at` and “a few minutes” (example is 300s).
- A single field that means “fully onboarded forever” — Stripe says there isn’t one; requirements can return.
- Whether Book should require `payouts_enabled` in addition to `charges_enabled` — Stripe allows charging without payouts.
- Whether to use Accounts v2 vs v1 Express `type` vs controller properties for a **new** platform.

---

## Sources

- [Understand how charges work in a Connect integration](https://docs.stripe.com/connect/charges)
- [Create destination charges](https://docs.stripe.com/connect/destination-charges) (Checkout and [PaymentIntent / Elements](https://docs.stripe.com/connect/destination-charges?platform=web&ui=elements))
- [Collect application fees (marketplace)](https://docs.stripe.com/connect/marketplace/tasks/app-fees)
- [Collect application fees (SaaS)](https://docs.stripe.com/connect/saas/tasks/app-fees)
- [Handle refunds and disputes](https://docs.stripe.com/connect/marketplace/tasks/refunds-disputes)
- [Refund and cancel payments](https://docs.stripe.com/refunds)
- [Using Express connected accounts](https://docs.stripe.com/connect/express-accounts)
- [Connected account types](https://docs.stripe.com/connect/accounts)
- [Stripe-hosted onboarding](https://docs.stripe.com/connect/hosted-onboarding)
- [Create an account](https://docs.stripe.com/api/accounts/create)
- [The Account object](https://docs.stripe.com/api/accounts/object)
- [Create an account link](https://docs.stripe.com/api/account_links/create)
- [The Account Link object](https://docs.stripe.com/api/account_links/object)
- [Migrate to controller properties](https://docs.stripe.com/connect/migrate-to-controller-properties)
- [Handle verification with the API](https://docs.stripe.com/connect/handling-api-verification)
- [API onboarding](https://docs.stripe.com/connect/api-onboarding)
- [Account capabilities](https://docs.stripe.com/connect/account-capabilities)
- [Connect webhooks](https://docs.stripe.com/connect/webhooks)
- [Receive Stripe events in your webhook endpoint](https://docs.stripe.com/webhooks)
- [Types of events](https://docs.stripe.com/api/events/types)
- [Create a webhook endpoint](https://docs.stripe.com/api/webhook_endpoints/create)
- [Idempotent requests](https://docs.stripe.com/api/idempotent_requests)
- [Create a PaymentIntent](https://docs.stripe.com/api/payment_intents/create)
- [The PaymentIntent object](https://docs.stripe.com/api/payment_intents/object)
- [Supported currencies](https://docs.stripe.com/currencies)
- [Define a custom pricing strategy](https://docs.stripe.com/connect/platform-pricing-tools/pricing-schemes)
- [Testing Stripe Connect](https://docs.stripe.com/connect/testing)
- [Pausing payments or payouts](https://docs.stripe.com/connect/pausing-payments-or-payouts-on-connected-accounts)
- [Build a full embedded integration](https://docs.stripe.com/connect/build-full-embedded-integration)
- [Using OAuth with Standard accounts](https://docs.stripe.com/connect/oauth-standard-accounts)
- [Using Standard connected accounts / OAuth](https://docs.stripe.com/connect/oauth)
- [Connect OAuth reference](https://docs.stripe.com/connect/oauth-reference)
- [Connect and the Accounts v2 API](https://docs.stripe.com/connect/accounts-v2)
- [Refund webhook update (2024-10-28)](https://docs.stripe.com/changelog/acacia/2024-10-28/refund-webhook-update.md)
