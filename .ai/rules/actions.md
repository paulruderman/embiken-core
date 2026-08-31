---
paths:
  - 'app/Actions/**'
---

# Actions

## Invokable domain Actions for writes
Every mutating use case is an Action under app/Actions/{Domain}/, named *Action. Use App\Actions\Action and AsAction. Invoke as a callable, never ->handle(). Method-inject when one Action needs another. handle() takes typed arguments, not Request. Wrap multi-model writes in DB::transaction() inside handle(). Do not ShouldQueue the Action.

## No broadcast in Actions
Do not ShouldBroadcast or fire broadcast events from handle(). Do not dispatch the same lifecycle event as $dispatchesEvents or BroadcastsEvents.

## Availability is the only occupancy seam
Book and Terminal Actions must call App\Services\Availability (quoteOccupancy, allocate, swapCandidates, swapAsset, release). Do not count occupancy in the Action or in Eloquent scopes. Pickup and return live on Terminal. Swap is SwapAssetAction in one transaction; do not cancel and rebook. Swap scopes, easiest first: same variant; same BikeModel different variant; any in-service bike (rewrite product_id; Terminal may requote or leave owed). When a package is present the new product_id must be on that package's pivot. Out and Assigned lines may both swap.

## Pickup, return, provisional expiry, heal, stage projector
Pickup and return write bike_reservation and Bike situation in the same Action; do not set situation from Reservation.stage (no brew SyncBikesSituation). Provisional expires when stored expires_at has passed (Cancelled unless stage is Confirmed or a row is Out). Bump expires_at on mutating Actions for that reservation, including a failed Connect capture; do not bump on reads or idle checkout. Minutes of TTL are a schema choice. Confirmed with no Out or In past ends_at becomes No Show: release unused lines, send prepping/staged bikes home. The reservation still occupies through ends_at plus the turnaround buffer — do not skip the buffer on No Show. A late original customer does not keep the slot. All rows In before ends_at caches Returned; all In and ends_at has passed caches Completed (immediately on return if already past ends_at; tick at ends_at if they returned early). Pickup and return may be a subset of lines; any Out caches stage Out. Early return does not lower owed. Do not fold stage at shop close. Next use of a bike with a stale Out heals that row to In (null checked_in_at) and release as of now, except Out on a Cancelled reservation (staff must return). Recompute stage in the mutating Action plus a scheduled tick. allocate never over-allocates.

## Confirmed without pay; occupancy ignores balance
Book auto-sets stage Confirmed when paid meets the package confirm threshold. Staff may set Confirmed with no payment (failed online pay, cash at desk). Do not block pickup or return on outstanding balance. Occupancy ignores Stripe. Record cash or other as a transaction with no PaymentIntent. Recompute owed and paid in the same Action that changes quote or transactions.

## Allocate uses reservation interval; pickup writes pivot and situation
allocate and quoteOccupancy overlap [starts_at, ends_at] inclusive on the reservation for each bike_reservation row, then block until after ends_at plus the effective turnaround buffer. Book and MyRental extend Actions refuse if starts_at or ends_at is outside a Location open interval or on a closed date; a closed night between them is allowed. quoteOccupancy and allocate do not check hours. An open interval may close after midnight. Duration steps still affect the quote (owed) only. Terminal and Filament may set any interval. Pickup writes bike_reservation Out and Bike rented_out plus bike_situation_reservation_id together. Return writes In and Location return situation: home (default) or back (then PutAwayAction to home). Terminal may set prepping or staged for that reservation before pickup; skip is allowed. Book does not write situation. Hours do not allocate.

## Book vs Terminal fleet: self_bookable
Book allocate and quoteOccupancy count in_service and self_bookable bikes of that product. Terminal may assign any in_service bike of that product (or a specific bike). Location bike-assignment policy: terminal (default) — Book never sets bike_id, Terminal persist on assign or pickup; book_may_pin — Book may set a specific bike_id (that bike is occupied); pickup_only — persist bike_id only at pickup, earlier assign is display-only and does not occupy a specific bike. Do not filter occupancy through an Allocation pool.

## Availability extras and heal-before-available
Availability overlap uses [starts_at, ends_at] inclusive plus the effective turnaround buffer after ends_at (Location minimum default 10 minutes, or a longer BikeModel padding). Do not count Cancelled reservations in class overlap; an Out bike on a Cancelled reservation is still not available. No Show still counts through ends_at plus buffer. Book and quoteOccupancy must honor the buffer. Terminal may ignore the buffer when allocating, swapping, or assigning; it must not overlap another reservation's [starts_at, ends_at]. Return at exactly ends_at is allowed. Also require in_service; Book also requires self_bookable. Blocking service with an optional window goes through Availability (no window = out until cleared). Do not filter allocate by Bike situation. quoteOccupancy never writes: a stale Out means not available. allocate and swapAsset heal a stale Out to In (null checked_in_at) and release as of now under the row lock, then proceed — except an Out line on a Cancelled reservation: do not heal it; that bike stays unavailable until ReturnAction. The scheduled tick also heals non-Cancelled stale Out only. Do not heal from an observer.

## Package confirm threshold, not always full pay
Book auto-sets stage Confirmed when paid meets the reservation's package confirm threshold (nothing through full owed). A $0/free package can become Confirmed with no charge. Staff may still set Confirmed with no payment. Do not require a successful full Connect capture to set Confirmed. The staff-writable stage is Confirmed, not Reserved.

## quoteOccupancy does not heal
quoteOccupancy is read-only. A bike with a stale Out is not available. Heal only in allocate, swapAsset (under row lock) or the scheduled tick, never an Out on a Cancelled reservation. Do not write tenant rows from quoteOccupancy.

## Package confirm bar is Book and MyRental, not Terminal
The package confirm threshold binds Book and MyRental (including MyRental extend on the new owed). Terminal may set Confirmed with no payment even when the package wants a deposit or full. Occupancy still ignores balance.

## Allocate only variants on the package pivot
When a reservation has a package, every bike_reservation product_id must exist on that package's variant pivot. Availability and quote only those products. Do not allocate a variant that is not on the package. Swap may change product_id only to a variant still on that pivot (or any in-service product when rental_package_id is null).

## Hourly quote 15-minute steps; null package skips pivot
Quote per_hour by rounding the reservation interval up to 15 minutes. Do not use exact fractional hours or whole-hour ceil. When rental_package_id is null (Terminal hand quote), skip package pivot membership and do not derive owed from rates. When a package is present, pivot membership still applies at Terminal.

## SwapAsset three scopes
SwapAssetAction: same variant; or same BikeModel another variant; or any in-service bike. Do not cancel and rebook. Terminal may requote or leave owed when product_id changes. Package pivot membership still applies when a package is present. Do not swap to a variant off the package.

## Extend is reservation-level Availability; no damage fee
ExtendAction sets reservation ends_at for all lines and must go through Availability. Do not extend Provisional. Do not put per-line end times. Do not implement extend as cancel plus rebook. Terminal may extend. MyRental may request an extend. Book does not extend. Do not add a damage fee Action on the reservation. PutAwayAction (back → home) is Terminal only when Location uses back. Counter and Manager may put away, park in_service, and open blocking service.

## Extend bill or comp; MyRental confirm; return can park a bike
Terminal may choose per extend: requote the new interval into owed (remind collect, no auto-capture) or leave owed unchanged. MyRental extend always requotes; ends_at commits only after paid meets the package confirm threshold on the new owed. Confirm bar binds Book and MyRental, not Terminal. A late return (after ends_at) prompts requote vs keep owed, same as extend; requote uses the interval through return time. Do not auto-requote. Early return does not lower owed; staff refund or set-owed if they credit. At return, Terminal (Counter or Manager) may set in_service false and/or open a blocking service row. Do not add owed for damage.

## Filament Reservation writes go through Actions
If Filament changes Reservation stage or owed, call the same Actions as Terminal would. Interval changes use ExtendAction and Availability. Add/remove lines use allocate and release. Cancel uses CancelAction (prompt if any line is Out). Managers may invoke RefundAction. Do not let a Filament resource write occupancy by saving the model directly. Filament must not run pickup, return, or Connect/cash checkout.

## Terminal cash or other; Stripe card readers later
Record Terminal payments as cash or other transactions with no PaymentIntent. Do not require Stripe Terminal in v1. Book Connect capture still creates a connect transaction and can Confirmed. Occupancy still ignores how they paid.

## No Show releases unused bikes; buffer still applies
The tick that writes No Show (Confirmed, never Out or In, past ends_at) releases unused lines and sends prepping/staged bikes home. Do not skip the turnaround buffer: class overlap still uses that reservation's [starts_at, ends_at] plus buffer. A late original customer does not keep the slot. Terminal may still serve them only if bikes remain. Do not treat No Show like Cancelled for occupancy (Cancelled drops class overlap; No Show does not).

## Remainder after deposit on MyRental or Terminal
After Confirmed, outstanding owed minus paid is collected on MyRental as another Connect capture (3% fee) or on Terminal as cash or other. Do not take a second Book PaymentIntent after Confirmed. Do not treat remainder as a new confirm threshold.

## CancelAction releases unused lines; Out stays Out
CancelAction sets stage Cancelled. Release occupancy for lines that are not Out (Assigned / never picked up); bikes that were only prepping or staged for this reservation go home. Do not auto In or back an Out line; prompt staff to confirm those bikes are in the shop, then they return as usual. Cancelled reservations do not occupy [starts_at, ends_at] for class allocate. An Out bike on a Cancelled reservation stays unavailable until ReturnAction. Do not RefundAction from cancel. Do not heal Cancelled Out on allocate.

## RefundAction; no customer self-refund or self-cancel
RefundAction records a refund transaction and recomputes paid. Connect charges refund through Stripe (application fee follows Stripe Connect refund). cash/other refunds are ledger rows only. Terminal Counter and Manager may invoke it. Filament Managers may invoke it. Book and MyRental must not self-refund or CancelAction. Do not release occupancy from a refund. Do not capture new charges in RefundAction.

## Customer mail after commit, no No Show mail
Send customer email from the Action after commit: confirm (signed MyRental URL), reminder before starts_at, receipt after Connect capture, thanks after return. Terminal may re-send the MyRental link. Do not email on No Show. Do not send marketing. Do not send mail from an occupancy observer.

## PaymentIntent amount from quote, 3% fee
Create the Book PaymentIntent with amount equal to the package confirm threshold (deposit cents, percent of owed, or full owed), not the remainder and not Stripe Price objects. Capture immediately; do not authorize-and-capture-later. Set application_fee_amount to 3% of that charge (integer cents, decide rounding in implementation). Do not take 3% of cash or other. If Express cannot charge yet, do not create a PaymentIntent; a none/$0 confirm package may still Confirmed on Book.

## Use lorisleiva/laravel-actions for AsAction
When implementing Actions, add lorisleiva/laravel-actions. App\Actions\Action uses AsAction. Invoke as a callable. Do not write a parallel AsAction. Do not add the package until that implementation work starts. Each Action ships asController and asCommand; see the asController rule below.

## asController API and asCommand on every Action
Every Action uses AsAction. handle() is the domain body: typed arguments, never Request or Command. At minimum ship asController (API; jsonResponse for JSON) and asCommand ($commandSignature plus asCommand). Authorize and validate on the Action (authorize(), rules()), not Form Requests. asController, asCommand, htmlResponse, and jsonResponse only map IO into handle(). Page GETs use htmlResponse (Inertia). Writes from the SPA and devices use jsonResponse. JSON is app IO, not a public partner API; use Laravel API Resources. Do not brand routes /api/v1 in v1. Register the Action class as the route. Tenant asCommand initializes tenancy before handle(). Do not add a parallel Http\Controller or Console\Command for the same use case. Do not put Inertia or HTTP responses in handle().

## asCommand args, Prompts, --tenant
asCommand signatures expose handle() arguments as optional Artisan arguments or options. If a needed value is missing, prompt with Laravel\Prompts (text, select, search), not Command::ask or Symfony Question. Under --no-interaction / -n, do not prompt; fail if required input is missing. Tenant Actions include {--tenant=} (tenant id or domain); resolve and initialize tenancy before handle(); prompt for tenant when omitted unless -n. Central-only Actions omit --tenant. Do not put Prompts in handle().

## Return situation and PutAwayAction
ReturnAction writes In plus Location return situation: home (default) or back. PutAwayAction (back to home) is Terminal only when the Location uses back. Counter and Manager may put away. Book and Filament do not put away.

## Late return prompts requote or keep owed
A late return (after ends_at) prompts requote vs keep owed, same as Terminal extend. Requote uses the interval through return time. Do not auto-requote. Do not add owed for damage.

## No customer CancelAction
Book and MyRental must not invoke CancelAction. Cancel is Terminal and Filament only.

## Partial pickup and return
Pickup and return may be a subset of lines. Any Out caches stage Out. All In before ends_at caches Returned. Early return does not lower owed.

## Counter may refund and park bikes
Terminal Counter and Manager may RefundAction, set in_service false, open blocking service, and PutAwayAction. Filament RefundAction stays Manager-only. Pickup, return, and put-away stay off Filament.

## Action JSON is SPA and staff Terminal
jsonResponse serves the SPA and staff Terminal. Device-token callers wait for the Device slice. Occupancy Actions do not take a Device.

## Tenant create includes Platform Manager; Suspend padlock
Create-tenant Action also inserts the Platform Manager Staff row. Suspend/unsuspend and Tenant delete (no drop DB, only while suspended) are Actions. Impersonation Action starts a Staff session as that Platform Manager on the tenant host. Do not impersonate an invitible Manager.

## Hours and assign stay in Actions, not Availability
Book and MyRental extend Actions check Location hours; quoteOccupancy does not. Persist a bike with allocate(..., bike_id). Display-only assign (pickup_only) never calls Availability. SwapAssetAction may auto-pick or show a picker from swapCandidates; do not put auto-pick in Availability. Pass channel Book or Terminal explicitly; do not infer it from the guard.

## Provisional expires_at bump including failed pay
Checkout allocate creates the Provisional lock. Bump Reservation expires_at on allocate, contact/waiver writes, and pay attempts (success or fail). Tick CancelAction when expires_at passed and stage is still Provisional with no Out. Back after lock amends the same reservation via allocate/release; do not CancelAction plus create from Book.
