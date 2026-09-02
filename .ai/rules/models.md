---
paths:
  - 'app/Models/**'
---

# Models

## Tenant is a shop, Location is in-tenant
A stancl Tenant is one shop operator. Location is a store in the tenant database, never a stancl tenant. Do not put tenant_id on tenant models. Never query tenant models on the central connection.

## Occupancy queries go through Availability, not scopes
Put complex queries in Eloquent scopes. Occupancy (overlap, buffer, in_service, self_bookable, blocking service) is the exception: App\Services\Availability only. Do not add an available() occupancy scope on Bike.

## Reservation and bike_reservation
A Reservation has many bike_reservation rows. Each row has product_id; bike_id is nullable until assigned. Class slots leave bike_id null; specific-bike sets bike_id and still stores the product. When bike_id is persisted is Location policy (terminal assign, Book pin, or pickup only). Do not put product_id or bike_id on the reservation as the occupancy unit. Do not add a parallel rentals table. The physical unit is Bike (bikes table), not Asset.

## Eloquent model events and BroadcastsEvents
Use $dispatchesEvents or #[ObservedBy] for lifecycle fan-out. Shop-floor live updates use BroadcastsEvents / dedicated ShouldBroadcast on tenant.{tenantId}.location.{locationId} (day board) or tenant.{tenantId}.cfd.{cfdDeviceId} (counter ticket). Payloads are DTOs (ids, statuses, the day's bikes and reservations), never Eloquent graphs. Model events must not allocate occupancy or replace Actions.

## Database-per-tenant, one Location, Staff and Customer
Each Tenant has its own database, created when the Tenant is created. Also create the one Location and a Manager (email; send a set-password invite; do not set a password on the tenant form) and start Express Account Link. Shop Filament invites additional Staff the same way. Do not seed catalog, packages, or hours. Do not drop tenant databases on tenant delete. v1: exactly one Location per tenant; no location switcher. Staff and Customer are separate Authenticatable models. Customer has required name, email, and phone. Email and phone are not unique: Book reuses on email match (updates name/phone); Terminal matches on email and phone then may force-create a new Customer. Do not add a Device model until the Device slice. Then Device is not Authenticatable; Sanctum tokens belong on Device.

## bike_reservation rental phase and stage cache
Rental phase lives on bike_reservation: status Assigned, Out, or In, plus optional checked_out_at and checked_in_at. Heal a stale Out to In with checked_in_at null, except Out on a Cancelled reservation (staff must return). Reservation stage is a cache: write Provisional, Confirmed, Cancelled; overwrite only with Out, Returned, Completed, or NoShow. Cancelled reservations do not occupy the class interval; an Out line on Cancelled still makes that bike unavailable. No Show still occupies [starts_at, ends_at] plus the turnaround buffer; unused lines are released and those bikes go home. Provisional vs Confirmed are not inferred from bikes. Bike has bike_situation_state (home, prepping, staged, rented_out, back) and bike_situation_reservation_id: required on prepping, staged, rented_out; optional on back; null on home. Location return situation chooses whether ReturnAction writes back or home.

## Shared reservation interval, inclusive occupancy plus buffer
Reservation holds starts_at and ends_at. In v1 every bike_reservation row shares that window; do not put a separate interval on the pivot. The renter occupies [starts_at, ends_at] inclusive (return at ends_at is on time). Quote duration is still elapsed ends_at minus starts_at (15-minute hourly steps, calendar-day count). Shop hours constrain Book (and MyRental extend) start and end instants only (each must fall in an open interval; an interval may close after midnight; overnight spanning a closed night is allowed), not allocate and not stage.

## Connect, owed/paid caches, transactions ledger
Renter charges use Stripe Connect to the shop, never the platform SaaS Stripe account. Platform shop subscriptions are ordinary Stripe (Cashier later), a different customer. Reservation has owed and paid columns as caches: owed is the current quote, paid is the sum of captured transactions. Money history is a transactions table belonging to the reservation (Connect, cash, other, refunds). Do not use is_paid as source of truth. Outstanding is owed minus paid (negative means refund).

## Catalog tree, product_id is variant, optional package on header
Catalog is BikeCategory → BikeModel → BikeModelVariant → Bike; Bike cannot skip layers. product_id on bike_reservation FKs to bike_model_variants; product() returns BikeModelVariant. Do not add a Product table or an add-ons table in v1. RentalPackage belongs to the Reservation, never to bike_reservation lines. Do not add an Allocation model; Bike has required bid (letter/number, unique in the tenant), in_service, and self_bookable. Optional photo on Bike, BikeModelVariant, and BikeModel; resolve display photo bike then variant then model. Do not add manufacturer serial, barcode, RFID, or QR in v1.

## Turnaround buffer: Location minimum, model may be longer
Location holds the shop-wide minimum turnaround buffer minutes (default 10). A BikeModel may set its own padding minutes; effective buffer is max(location minimum, model padding or 0). Availability blocks the next rental until after ends_at plus that buffer. Do not put buffer on the reservation interval or on Bike. Do not allow a model padding below the Location minimum to shrink the shop floor.

## Package required on Book, nullable at Terminal only
RentalPackage belongs to the Reservation header, never to bike_reservation lines. Book always sets rental_package_id (default or pick among Book-visible packages). Terminal may leave it null only for a hand quote. A free package is valid. Do not keep a global pay-in-full rule; the package defines Book's Provisional→Confirmed payment threshold.

## Package meters including calendar day
A RentalPackage has one meter: none, per_hour, per_line, or per_calendar_day. Confirm threshold is separate (none, deposit, or full). Meter none is the free package. Do not add a rule list or expression language. per_calendar_day counts distinct calendar dates in the location timezone that intersect [starts_at, ends_at]; an ends_at exactly at midnight does not add that date. Do not use ceil(hours/24). Confirm deposit on a package is either fixed cents or a percent of owed, not both on the same package.

## Package-variant pivot is the price list and offer membership
rental_package to bike_model_variant is a pivot with rate_cents. product_id on the pivot is the variant. No row means that variant is not in the package (not quoted, not allocated under it). rate_cents is per hour, per calendar day, or per line according to the package meter. Meter none still has pivot rows; rate_cents is 0. Do not put rate_cents on the variant or a single amount on the package header as the product price.

## Keep RentalPackage; hourly 15-minute ceil; Terminal null package
Do not rename RentalPackage to Plan. per_hour quotes (ends_at - starts_at) rounded up to 15-minute steps, then times pivot rate_cents. Optional min/max duration on the package constrains quoting only, not occupancy. Terminal with a null package may line any in-service product and set owed by hand. A reservation that has a package still requires every line on that package's variant pivot.

## No Down situation; damage is not occupancy
Do not add a Down bike situation. Damage does not occupy and does not change owed. Extend changes reservation ends_at only.

## CFD device pairs to a Terminal device (later)
When the Device slice lands, a CFD Device has paired_terminal_device_id (the Terminal device it mirrors). Many CFD/Terminal pairs per Location are allowed. Do not identify a CFD channel as screenId; the Screen surface uses the location channel. Shop-operable Terminal has no Device row.

## Hours live on Location
Location stores timezone, currency (default usd), one or more open intervals per weekday (close may be the next calendar day), optional all-day closed dates, the shop-wide minimum turnaround buffer minutes (default 10), bike-assignment policy (terminal, book_may_pin, or pickup_only), and return situation (home, or back then put-away; default home). Tenant create leaves hours empty. Book and MyRental extend require starts_at and ends_at each to fall during an open interval and not on a closed date. A closed night between start and end is allowed. Terminal and Filament may override. Store hours do not allocate and do not fold stage. Do not put hours on RentalPackage. Do not run a shop-close job that changes reservations. Do not add tax onto owed in v1.

## Transaction kinds: connect, cash, other
transactions.kind (or equivalent) includes connect, cash, other, and refunds. Refunds decrease paid. Terminal v1 takes cash or other only; no PaymentIntent and no Stripe card readers yet. Book still uses Connect PaymentIntents (capture of the confirm amount). paid sums all captured kinds minus refunds. Do not invent a second ledger for other.

## Quote in Embiken; 3% Connect fee; other has a note
Connect charges send Embiken's confirm-threshold total as the PaymentIntent amount and capture immediately. Remainder is a later charge, not this PaymentIntent. Do not create Stripe Products or Prices for rentals; shops do not set rental prices in Stripe. application_fee is 3% of the Connect charge. cash and other have no application fee. other may include a free-text note. Platform shop subscriptions are a separate Stripe/Cashier customer, not this 3%.

## Connect Express only; no Standard attach
Rental Connect is Express only. Create a new Express connected account per tenant shop. Do not OAuth or attach the shop's existing full Stripe account. Quote still lives in Embiken; PaymentIntent amount is our total. Platform SaaS subscriptions stay a separate Stripe.

## Customer match is not unique
Customer email and phone are required and indexed, not unique. Book reuses on email match (updates name/phone). Terminal matches on email and phone and may force-create a new Customer.

## Hours are intervals that may span midnight
Location hours are one or more open intervals per weekday; an interval may close on the next calendar day. Closed dates are all-day. Book and MyRental extend require starts_at and ends_at each inside an open interval.

## No tax; Location currency; no add-ons
owed is the package quote in Location currency (default usd). Do not add tax in v1. Do not add add-on or helmet line tables. Book reuses Customer on email match.

## Bike bid and photo fallback
Bike has required bid: a shop-facing letter/number code, unique in the tenant. Do not add manufacturer serial, barcode, RFID, or QR in v1. Optional photo on Bike, BikeModelVariant, and BikeModel. Resolve the display photo bike then variant then model.

## No Device model until Device slice
Do not add a Device model in shop-operable schema. When the Device slice lands, Device is not Authenticatable and Sanctum tokens belong on Device. Occupancy models are unchanged.

## User vs Staff vs Platform Manager; Suspend; no DB drop
User is central, one admin class, disable not delete. Staff is tenant. Tenant create: invitible Manager plus Platform Manager (Manager role, not a person, no password). Suspend is a Tenant state; delete removes central Tenant and Domain rows only while suspended and must not drop the tenant database. No restore.

## Party is N bike_reservation rows, not qty
A Reservation has many lines. Quantity is extra rows, never a qty column. Do not add a Rider model or extra Customers for people in the party. Do not put product_id or bike_id on the Reservation header as the occupancy unit.

## Service tickets occupy separately from in_service
A bike is unavailable when in_service is false or when a service request has blocks_usage and ServiceStage::occupiesWhenBlocking() (not resolved or cancelled). Staff may set in_service at any time. Opening or resolving a ticket must not write in_service. ServiceStage::Blocked is work-cannot-proceed, not occupancy. Optional starts_at/ends_at on the ticket is the occupancy window (null = while the ticket occupies). Staff FKs, never User. No checklists or maintenance_schedules in shop-operable.

## Optional rider nickname and height on the line
bike_reservation may store optional rider_name (Terminal nickname for large parties) and rider_height_cm. These are not a Rider model or a Customer. Variant stores min/max ideal and extended rider height in cm. Screen must not render rider_name.
