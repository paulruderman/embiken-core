# Embiken

SaaS for independent bike rental shops. Tenancy is stancl/tenancy: **database-per-tenant**, identified by **domain or subdomain**. A **Tenant** is one shop operator. A **Location** is a store in the tenant database, never a stancl tenant. v1 is one Location per tenant; do not add a location picker or path.

Mutating use cases are invokable Actions. **Availability** is the only occupancy seam (intervals on `bike_reservation` rows, under row locks; no occupancy ledger). A **Reservation** has many **bike_reservation** rows: product required, `bike_id` nullable until assigned. Reservation `stage` is a cache: staff write Provisional, Reserved, or Cancelled; never drive bike state from `stage`. Provisional is a short cart lock (about 10–15 minutes) then Cancelled if not Reserved or picked up. Shop Filament is configuration, not a customer register.

New tenants get a subdomain immediately; a custom domain is an extra Domain row later. The central apex is Platform Filament only. Ops may open tenant surfaces at `/t/{tenant}/…` on the apex (platform auth, or local); those URLs are not customer links and Wayfinder must not emit them for the SPA.

## Surfaces

One Vue SPA except for the Filament parts. Wayfinder for every frontend route. `GET /` redirects to `/book`.

| Surface | Path | Who | Role |
| --- | --- | --- | --- |
| Book | `/book` | public / customer | Browse, provisional, reserve, pay deposit |
| MyRental | `/myrental` | customer | That reservation before and during the rental |
| Station | `/station` | staff + bound device | Counter register: walk-in, assign/swap, checkout, check-in, pickup/return, damage, extend |
| CFD | `/cfd` | device | Counter customer display. No staff controls |
| Pad | `/pad` | staff + bound device | Floor tablet; same Actions as station |
| Screen | `/screen` | device | Shop availability board. Echo after first paint. No controls |
| Shop Filament | `/manage` | managers | Catalog, fleet, hours, devices, pricing, staff. Never checkout |
| Platform Filament | central host | platform | Tenants, domains, billing later. Not shop data |

Pages live under `resources/js/pages/{Book,MyRental,Station,Cfd,Pad,Screen}/`.

Auth: platform users (central DB, `User`). **Staff** and **Customer** are separate tenant authenticatable models with separate guards. Staff roles: **Manager** (`/manage` + station/pad) vs **Counter** (station/pad only). Devices are not users: a Device bound to Location + surface, Sanctum token, paired with a one-time code in shop Filament. Device-only in daily operation: **cfd**, **screen**. Staff plus device: **station**, **pad**. Book is guest until reserve; then a Customer exists; MyRental is a signed or magic link. There is no kiosk surface; pickup/return is station and pad.
