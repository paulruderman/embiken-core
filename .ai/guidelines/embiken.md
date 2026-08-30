# Embiken

SaaS for independent bike rental shops. Tenancy is stancl/tenancy: **database-per-tenant**, identified by **domain or subdomain**. A **Tenant** is one shop operator. A **Location** is a store in the tenant database, never a stancl tenant. v1 is one Location per tenant; do not add a location picker or path.

Mutating use cases are invokable Actions. **Availability** is the only occupancy seam (each `bike_reservation` occupies the reservation’s half-open `[starts_at, ends_at)`, under row locks; no occupancy ledger). Conflict also applies the BikeModel turnaround buffer (renter interval unchanged), `in_service`, Book’s `self_bookable`, and blocking service with an optional window. Situation is not an allocate filter. `quoteOccupancy` does not write; a stale Out is not available until mutating Availability or the scheduled tick heals it. Data-model ancestor: `../embiken-brew`. Catalog is **BikeCategory → BikeModel → BikeModelVariant → Bike** (required chain). `product_id` on `bike_reservation` is the variant; there is no `Product` table; `product()` returns `BikeModelVariant`. Rates live on a **package × variant** pivot (`rate_cents`); a missing row means that variant is not in the offer (e-bike-only packages, etc.). Meter `per_line` is the fixed amount for that line for the whole interval. Meter `none` still uses the pivot for membership; `rate_cents` is 0. **RentalPackage** lives on the Reservation header, never on lines. Book always has a package (auto-select if one Book-visible, else the customer picks). Station may omit only for hand-entered quotes. A free package is valid. A package has one meter (`none`, `per_hour`, `per_line`, `per_calendar_day`) and a Book confirm threshold (`none`, deposit as cents or percent, or full). `per_calendar_day` counts distinct shop-timezone dates that intersect `[starts_at, ends_at)`. The package—not a global rule—defines what payment Book needs to leave Provisional. Staff may still set Confirmed without payment (package bar is Book-only). No Allocation pool; Bike has `in_service` and `self_bookable` (Book counts both; Station/Pad may assign any in-service bike). Core names: **Reservation** (not Booking), pivot `bike_reservation` (product required, `bike_id` nullable until assigned). Reservation `stage` is a cache: staff write Provisional, Confirmed, or Cancelled; never drive bike state from `stage` (Brew’s `SyncBikesSituationForBookingStage` is gone). Provisional is a short cart lock (about 10–15 minutes) then Cancelled if not Confirmed or picked up. Renters pay shops via **Stripe Connect**. Book auto-flips Provisional → Confirmed when the package’s confirm threshold is met (`paid` vs `owed`); not always pay-in-full. Reservation stores **owed** and **paid** (caches); **transactions** are the money history. Shop Filament is configuration, not a customer register.

New tenants get a subdomain immediately; a custom domain is an extra Domain row later. The central apex is Platform Filament only. Ops may open tenant surfaces at `/t/{tenant}/…` on the apex (platform auth, or local); those URLs are not customer links and Wayfinder must not emit them for the SPA.

## Surfaces

One Vue SPA except for the Filament parts. Wayfinder for every frontend route. `GET /` redirects to `/book`.

| Surface | Path | Who | Role |
| --- | --- | --- | --- |
| Book | `/book` | public / customer | Browse, provisional, pay the package confirm threshold to confirm |
| MyRental | `/myrental` | customer | That reservation before and during the rental |
| Station | `/station` | staff + bound device | Counter register: walk-in, assign/swap, checkout, check-in, pickup/return, damage, extend |
| CFD | `/cfd` | device | Counter customer display. No staff controls |
| Pad | `/pad` | staff + bound device | Floor tablet; same Actions as station |
| Screen | `/screen` | device | Shop availability board. Echo after first paint. No controls |
| Shop Filament | `/manage` | managers | Catalog, fleet, hours, devices, pricing, staff. Never checkout |
| Platform Filament | central host | platform | Tenants, domains, shop subscriptions later. Not shop rental checkout |

Pages live under `resources/js/pages/{Book,MyRental,Station,Cfd,Pad,Screen}/`.

Auth: platform users (central DB, `User`). **Staff** and **Customer** are separate tenant authenticatable models with separate guards. Staff roles: **Manager** (`/manage` + station/pad) vs **Counter** (station/pad only). Devices are not users: a Device bound to Location + surface, Sanctum token, paired with a one-time code in shop Filament. Device-only in daily operation: **cfd**, **screen**. Staff plus device: **station**, **pad**. Book is guest until reserve; then a Customer exists; MyRental is a signed or magic link. There is no kiosk surface; pickup/return is station and pad.

## How we write decisions

Never hand-edit `AGENTS.md`, `CLAUDE.md`, or `.ai/rules/boost/**`. Always-on law goes in `.ai/guidelines/embiken.md`, then `php artisan boost:update --no-discover --no-interaction`. Path-scoped law via Boost `record-rule`. Do not duplicate essays.
