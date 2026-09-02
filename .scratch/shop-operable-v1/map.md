# Shop-operable v1 spec family

## Destination

A family of six specs covering shop-operable Embiken v1, ready to hand to `/to-spec` / implementers, with nothing left to decide between them. This map does not write the app.

**Envelope:** Tenant, Location, catalog/hours/packages, Availability, Book, Terminal (staff session), Shop Filament, Connect, Platform Filament including User admin.

**Specs (seams, not page trees):**

1. Platform tenancy and Users
2. Shop catalog, hours, packages, Staff
3. Availability
4. Connect and money
5. Book
6. Terminal (same Actions as Shop Filament Reservation buttons; Filament never pickup/return/checkout)

## Notes

- Vocabulary: `CONTEXT.md`, `.ai/guidelines/embiken.md`, and `.ai/rules`. Not: Booking, Station, Pad, Product table, Allocation.
- Skills: grilling + codebase-design on every HITL ticket; prototype skill for Terminal POS; laravel-best-practices when PHP is in scope. Call `to-spec` only when a spec’s decisions are done.
- New product decisions: guidelines + Boost `record-rule`. Specs cite; they do not duplicate essays.
- Law is soft: a ticket may amend a recorded rule with an explicit amendment.
- **Platform Manager / impersonation:** User impersonates only the Platform Manager on the tenant host. Written in [Platform User admin envelope](issues/05-platform-user-admin.md) and [ADR-0001](../../docs/adr/0001-platform-manager-impersonation.md).
- **Signed MyRental URL:** persist a token (mint once); show it on Book Confirmed whenever the Customer session resumes (not one-shot); Terminal may reveal (copy, not mail); `GET /myrental` 404s until a later map; access `ends_at + 3 days`. No mail in this family. [Book screens and empty states](issues/07-book-screens-and-empty-states.md)
- Remainder after deposit, extend, and cancel are Terminal-only here (no MyRental pages).
- Prototype Terminal POS only; Book is a written screen list.
- Tracker: GitHub Issues via `gh`. Canonical map: [Shop-operable v1 spec family](https://github.com/paulruderman/embiken-core/issues/1). Local files under this directory are a working copy of tickets and research assets.
- **Stack (do not re-pick):** `stancl/tenancy` `^3.10`, Filament `^5.0`, `lorisleiva/laravel-actions` `^2.12`, `stripe/stripe-php` `^21.3` (no Cashier), `laravel/reverb` `^1.11`, `pinia` `^4.0`, `laravel-echo` `^2.4`, `pusher-js` `^8.6`. No Filament `HasTenants`. [Stack packages for Laravel 13](issues/02-stack-packages-laravel-13.md)
- `../embiken-brew` is the data-model ancestor, not the spec.

## Decisions so far

- [Amend occupancy broadcast after commit](issues/12-amend-broadcast-after-commit.md): occupancy events are `ShouldBroadcastNow` + `ShouldDispatchAfterCommit`; Laravel 13 has no `ShouldBroadcastAfterCommit`. [.ai/rules/events.md](../../.ai/rules/events.md)
- [Book screens and empty states](issues/07-book-screens-and-empty-states.md): four screens (interval, offer, checkout allocate-then-pay, Confirmed); distinct copy for not-configured / Suspended / Express-off; `expires_at` bumped on mutating Actions including failed pay; `/book` resumes same-browser Customer session; signed URL stays visible on Confirmed. [ADR-0003](../../docs/adr/0003-book-resume-and-provisional-ttl.md)
- [Availability module interface](issues/06-availability-module-interface.md): occupancy seam only (hours and money stay in Actions); channel Book|Terminal; `quoteOccupancy` returns remaining; `allocate` ensures lines for a proposed interval; no `assign()`; `swapCandidates` + `swapAsset`; one `OccupancyUnavailable` with a closed reason. [ADR-0002](../../docs/adr/0002-availability-module-interface.md)
- [Platform User admin envelope](issues/05-platform-user-admin.md): one User class (Artisan first, then invite; disable not delete); Domains + Express status/retry on platform; Suspend padlocks the shop host; delete only while suspended, no drop DB, no restore; User impersonates the **Platform Manager** on the tenant host (`/manage` + `/terminal`, including while suspended). [ADR-0001](../../docs/adr/0001-platform-manager-impersonation.md)
- [Amend Device pairing out of shop-operable Terminal](issues/04-amend-device-pairing.md): Terminal is staff session only; Device/Sanctum pairing, CFD pairing, and Filament Devices wait; location channel authorizes staff; occupancy unchanged.
- [Stack packages for Laravel 13](issues/02-stack-packages-laravel-13.md): stancl `^3.10` (not v4), Filament `^5.0`, laravel-actions `^2.12`, `stripe/stripe-php` `^21.3` and no Cashier, Reverb `^1.11`, Pinia `^4` + Echo `^2.4` + `pusher-js` `^8.6`. No `HasTenants`. [findings](research/stack-packages.md)
- [Stripe Connect Express facts for Book](issues/01-stripe-connect-express-facts.md): destination charges (`transfer_data[destination]` + integer `application_fee_amount`); Account Link `return_url` is not “can charge”; gate on `charges_enabled` (plus `transfers` active); Stripe does not round 3% for you. [findings](research/stripe-connect-express.md)
- [Reverb Echo Pinia for Terminal day store](issues/03-reverb-echo-pinia-terminal.md): staff session on `/broadcasting/auth` (not Sanctum); seed Pinia from Inertia first-paint props on a persistent Terminal layout; occupancy is `ShouldBroadcastNow` + Laravel 13 `ShouldDispatchAfterCommit` (`ShouldBroadcastAfterCommit` is gone). [findings](research/reverb-echo-pinia.md)

## Not yet specified

- Location-channel DTO field list (after Terminal prototype; realtime wiring is in [Reverb Echo Pinia for Terminal day store](issues/03-reverb-echo-pinia-terminal.md))
- Swap: auto-pick vs staff picker (UX after Terminal prototype; [Availability module interface](issues/06-availability-module-interface.md) already returns ranked `swapCandidates`)
- Scheduler cadence and per-tenant fan-out (after Action inventory)
- Filament form schemas and empty-state copy
- Book branding (logo, colors) and Location name vs Tenant name as shop title
- Waiver legal text source and versioning
- i18n vs English-only
- Photo storage (path vs media library)
- Enum representation (backed enum vs string)
- Test harness beyond SQLite `:memory:` tenancy

## Out of scope

- CFD, Screen, and MyRental **pages** (the signed URL is minted here; pages later)
- Emails (confirm, reminder, Connect receipt, return thanks)
- Ops `/t/{tenant}/…`
- Device pairing, Sanctum device tokens, CFD `paired_terminal_device_id`, Shop Filament Devices resource
- Tax, add-ons, Pad, Allocation, Product table, RFID/barcode/QR, stored signature, shop subscriptions, Terminal card readers, public `/api/v1`
