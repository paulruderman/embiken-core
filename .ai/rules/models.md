---
paths:
  - 'app/Models/**'
---

# Models

## Tenant is a shop, Location is in-tenant
A stancl Tenant is one shop operator. Location is a store in the tenant database, never a stancl tenant. Do not put tenant_id on tenant models. Never query tenant models on the central connection.

## Reservation and bike_reservation
A Reservation has many bike_reservation rows. Each row has product_id; bike_id is nullable until assigned. Class slots leave bike_id null; specific-bike sets bike_id and still stores the product. Do not put product_id or bike_id on the reservation as the occupancy unit. Do not add a parallel rentals table. The physical unit is Bike (bikes table), not Asset.

## Eloquent model events and BroadcastsEvents
Use $dispatchesEvents or #[ObservedBy] for lifecycle fan-out. Shop-floor live updates use BroadcastsEvents / dedicated ShouldBroadcast on tenant.{tenantId}.location.{locationId} (day board) or tenant.{tenantId}.cfd.{cfdDeviceId} (counter ticket). Payloads are DTOs (ids, statuses, the day's bikes and reservations), never Eloquent graphs. Model events must not allocate occupancy or replace Actions.

## Database-per-tenant, one Location, Staff and Customer
Each Tenant has its own database, created when the Tenant is created. Do not drop tenant databases on tenant delete. v1: exactly one Location per tenant; no location switcher. Staff and Customer are separate Authenticatable models. Device is not Authenticatable; Sanctum tokens belong on Device.

## bike_reservation rental phase and stage cache
Rental phase lives on bike_reservation: status Assigned, Out, or In, plus optional checked_out_at and checked_in_at. Heal a stale Out to In with checked_in_at null. Reservation stage is a cache: write Provisional, Confirmed, Cancelled; overwrite only with Out, Returned, Completed, or NoShow. Provisional vs Confirmed are not inferred from bikes. Bike has bike_situation_state (home, prepping, staged, rented_out, back) and bike_situation_reservation_id: required on prepping, staged, rented_out; optional on back; null on home.

## Shared reservation interval, half-open occupancy
Reservation holds starts_at and ends_at. In v1 every bike_reservation row shares that window; do not put a separate interval on the pivot. Occupancy is the half-open interval [starts_at, ends_at). Shop hours belong to quoting, not allocate.

## Connect, owed/paid caches, transactions ledger
Renter charges use Stripe Connect to the shop, never the platform SaaS Stripe account. Platform shop subscriptions are ordinary Stripe (Cashier later), a different customer. Reservation has owed and paid columns as caches: owed is the current quote, paid is the sum of captured transactions. Money history is a transactions table belonging to the reservation (Connect, cash, other, refunds). Do not use is_paid as source of truth. Outstanding is owed minus paid (negative means refund).

## Catalog tree, product_id is variant, optional package on header
Catalog is BikeCategory → BikeModel → BikeModelVariant → Bike; Bike cannot skip layers. product_id on bike_reservation FKs to bike_model_variants; product() returns BikeModelVariant. Do not add a Product table. RentalPackage belongs to the Reservation, never to bike_reservation lines. Do not add an Allocation model; Bike has in_service and self_bookable.

## Turnaround buffer on BikeModel
BikeModel holds turnaround buffer minutes. Availability uses it to stretch the conflict window past ends_at. Do not put buffer on the reservation interval or on Bike.

## Package required on Book, nullable at Terminal only
RentalPackage belongs to the Reservation header, never to bike_reservation lines. Book always sets rental_package_id (default or pick among Book-visible packages). Terminal may leave it null only for a hand quote. A free package is valid. Do not keep a global pay-in-full rule; the package defines Book's Provisional→Confirmed payment threshold.

## Package meters including calendar day
A RentalPackage has one meter: none, per_hour, per_line, or per_calendar_day. Confirm threshold is separate (none, deposit, or full). Meter none is the free package. Do not add a rule list or expression language. per_calendar_day counts distinct calendar dates in the location timezone that intersect [starts_at, ends_at); an ends_at exactly at midnight does not add that date. Do not use ceil(hours/24). Confirm deposit on a package is either fixed cents or a percent of owed, not both on the same package.

## Package-variant pivot is the price list and offer membership
rental_package to bike_model_variant is a pivot with rate_cents. product_id on the pivot is the variant. No row means that variant is not in the package (not quoted, not allocated under it). rate_cents is per hour, per calendar day, or per line according to the package meter. Meter none still has pivot rows; rate_cents is 0. Do not put rate_cents on the variant or a single amount on the package header as the product price.

## Keep RentalPackage; hourly 15-minute ceil; Terminal null package
Do not rename RentalPackage to Plan. per_hour quotes (ends_at - starts_at) rounded up to 15-minute steps, then times pivot rate_cents. Optional min/max duration on the package constrains quoting only, not occupancy. Terminal with a null package may line any in-service product and set owed by hand. A reservation that has a package still requires every line on that package's variant pivot.

## No Down situation; damage is not occupancy
Do not add a Down bike situation. Damage does not occupy and does not change owed. Extend changes reservation ends_at only.

## CFD device pairs to a Terminal device
A CFD Device has paired_terminal_device_id (the Terminal device it mirrors). Many CFD/Terminal pairs per Location are allowed. Do not identify a CFD channel as screenId; the Screen surface uses the location channel.

## Hours live on Location
Location stores timezone and weekly opening hours plus optional closed dates. Shop hours constrain quoting only, not Availability allocate. Do not put hours on RentalPackage.

## Transaction kinds: connect, cash, other
transactions.kind (or equivalent) includes connect, cash, and other. Terminal v1 takes cash or other only; no PaymentIntent and no Stripe card readers yet. Book still uses Connect PaymentIntents. paid sums all captured kinds. Do not invent a second ledger for other.

## Quote in Embiken; 3% Connect fee; other has a note
Connect charges send Embiken's computed total (owed delta / confirm amount) as the PaymentIntent amount. Do not create Stripe Products or Prices for rentals; shops do not set rental prices in Stripe. application_fee is 3% of the Connect charge. cash and other have no application fee. other may include a free-text note. Platform shop subscriptions are a separate Stripe/Cashier customer, not this 3%.

## Connect Express only; no Standard attach
Rental Connect is Express only. Create a new Express connected account per tenant shop. Do not OAuth or attach the shop's existing full Stripe account. Quote still lives in Embiken; PaymentIntent amount is our total. Platform SaaS subscriptions stay a separate Stripe.
