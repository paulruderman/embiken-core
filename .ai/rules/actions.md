---
paths:
  - 'app/Actions/**'
---

# Actions

## Invokable domain Actions for writes
Every mutating use case is an Action under app/Actions/{Domain}/, named *Action. Use App\Actions\Action and AsAction. Invoke as a callable, never ->handle(). Method-inject the Action. handle() takes typed arguments, not Request. Authorize and validate in HTTP. Wrap multi-model writes in DB::transaction() inside handle(). Do not ShouldQueue the Action.

## No broadcast in Actions
Do not ShouldBroadcast or fire broadcast events from handle(). Do not dispatch the same lifecycle event as $dispatchesEvents or BroadcastsEvents.

## Availability is the only occupancy seam
Book and Terminal Actions must call Availability (allocate, quoteOccupancy, swapAsset, release). Do not count occupancy in the Action. Pickup and return live on Terminal. Swap is SwapAssetAction in one transaction; do not cancel and rebook.

## Pickup, return, provisional expiry, heal, stage projector
Pickup and return write bike_reservation and Bike situation in the same Action; do not set situation from Reservation.stage (no brew SyncBikesSituation). Provisional expires after a short cart-lock TTL (about 10-15 minutes) to Cancelled unless stage is Confirmed or a row is Out. Confirmed with no Out or In past ends_at becomes No Show. All rows In before ends_at/close caches Returned; at or after caches Completed. Next use of a bike with a stale Out heals that row to In (null checked_in_at) and release as of now. Recompute stage in the mutating Action plus a scheduled tick. allocate never over-allocates.

## Confirmed without pay; occupancy ignores balance
Book auto-sets stage Confirmed when paid meets the package confirm threshold. Staff may set Confirmed with no payment (failed online pay, cash at desk). Do not block pickup or return on outstanding balance. Occupancy ignores Stripe. Record cash or other as a transaction with no PaymentIntent. Recompute owed and paid in the same Action that changes quote or transactions.

## Allocate uses reservation interval; pickup writes pivot and situation
allocate and quoteOccupancy overlap [starts_at, ends_at) on the reservation for each bike_reservation row. Shop hours and duration steps affect the quote only. Pickup writes bike_reservation Out and Bike rented_out plus bike_situation_reservation_id together; return writes In and back or home.

## Book vs Terminal fleet: self_bookable
Book allocate and quoteOccupancy count in_service and self_bookable bikes of that product. Terminal may assign any in_service bike of that product (or a specific bike). Do not filter occupancy through an Allocation pool.

## Availability extras and heal-before-available
Availability overlap uses [starts_at, ends_at) plus BikeModel turnaround buffer for conflict only. Also require in_service; Book also requires self_bookable. Blocking service with an optional window goes through Availability (no window = out until cleared). Do not filter allocate by Bike situation. quoteOccupancy never writes: a stale Out means not available. allocate, swap, and assign heal that row to In (null checked_in_at) and release as of now under the row lock, then proceed. The scheduled tick also heals. Do not heal from an observer.

## Package confirm threshold, not always full pay
Book auto-sets stage Confirmed when paid meets the reservation's package confirm threshold (nothing through full owed). A $0/free package can become Confirmed with no charge. Staff may still set Confirmed with no payment. Do not require a successful full Connect capture to set Confirmed. The staff-writable stage is Confirmed, not Reserved.

## quoteOccupancy does not heal
quoteOccupancy is read-only. A bike with a stale Out is not available. Heal only in allocate, swap, assign (under row lock) or the scheduled tick. Do not write tenant rows from quoteOccupancy.

## Package confirm bar is Book and MyRental, not Terminal
The package confirm threshold binds Book and MyRental (including MyRental extend on the new owed). Terminal may set Confirmed with no payment even when the package wants a deposit or full. Occupancy still ignores balance.

## Allocate only variants on the package pivot
When a reservation has a package, every bike_reservation product_id must exist on that package's variant pivot. Availability and quote only those products. Do not allocate a variant that is not on the package. Swap stays inside the package's variants.

## Hourly quote 15-minute steps; null package skips pivot
Quote per_hour by rounding the reservation interval up to 15 minutes. Do not use exact fractional hours or whole-hour ceil. When rental_package_id is null (Terminal hand quote), skip package pivot membership and do not derive owed from rates. When a package is present, pivot membership still applies at Terminal.

## Extend is reservation-level Availability; no damage fee
ExtendAction sets reservation ends_at for all lines and must go through Availability. Do not extend Provisional. Do not put per-line end times. Do not implement extend as cancel plus rebook. Terminal may extend. MyRental may request an extend. Book does not extend. Do not add a damage fee Action on the reservation.

## Extend bill or comp; MyRental confirm; return can park a bike
Terminal may choose per extend: requote the new interval into owed (remind collect, no auto-capture) or leave owed unchanged. MyRental extend always requotes; ends_at commits only after paid meets the package confirm threshold on the new owed. Confirm bar binds Book and MyRental, not Terminal. At return, Terminal may set in_service false and/or open a blocking service row. Do not add owed for damage.

## Filament Reservation writes go through Actions
If Filament changes Reservation stage or owed, call the same Actions as Terminal would. Interval changes use ExtendAction and Availability. Add/remove lines use allocate and release. Do not let a Filament resource write occupancy by saving the model directly. Filament must not run pickup, return, or Connect/cash checkout.

## Terminal cash or other; Stripe card readers later
Record Terminal payments as cash or other transactions with no PaymentIntent. Do not require Stripe Terminal in v1. Book Connect capture still creates a connect transaction and can Confirmed. Occupancy still ignores how they paid.

## PaymentIntent amount from quote, 3% fee
Create the Book PaymentIntent with amount from the reservation quote, not from Stripe Price objects. Set application_fee_amount to 3% of that charge (integer cents, decide rounding in implementation). Do not take 3% of cash or other.

## Use lorisleiva/laravel-actions for AsAction
When implementing Actions, add lorisleiva/laravel-actions. App\Actions\Action uses AsAction. Invoke as a callable. Do not write a parallel AsAction. Do not add the package until that implementation work starts.
