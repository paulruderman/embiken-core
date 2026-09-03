---
paths:
  - 'resources/js/**'
---

# Js

## Echo with useEcho
configureEcho({ broadcaster: 'reverb' }) from @laravel/echo-vue. Shop-operable Terminal subscribes with useEcho to tenant.{tenantId}.location.{locationId} only (staff session). Screen (later) uses that same location channel. Terminal does not subscribe to a CFD channel until the Device slice; then it also listens on each paired tenant.{tenantId}.cfd.{cfdDeviceId}. CFD (later) subscribes to its own CFD channel. Leave on unmount. First paint hydrates a shared Pinia store with the day's bikes and reservations. Echo handlers patch that store. Components read Pinia as the reactive source of truth. Do not refetch the full fleet on each event. Do not use a compact counts-only snapshot as the Screen's store.

## Leading-dot model broadcast names
Model broadcasts are not App\Events classes. Listen with a leading-dot name: useEcho(..., '.ReservationUpdated').

## Pinia is shop-floor realtime truth
Use a shared Pinia store for shop-floor realtime state. Hydrate on first paint; patch from Echo; components read the store. Do not keep a second local copy of bikes or reservations in a page. Pinia is not in package.json yet; add it when this is implemented, not a different client store.

## Day-store window, PII on wire, Screen must not show it
Pinia day store is Terminal (and Screen when that surface exists). Hydrate all bikes, plus reservations whose [starts_at, ends_at] intersects today in the location timezone, plus any reservation that currently has a bike not in situation home. Location payload may include customer display name and owed/paid, and bike bid. Screen UI must not show customer PII or money; Terminal may. Screen may show bid. CFD (later) uses a separate ticket Pinia store for the paired Terminal's focused reservation. Book and MyRental do not hydrate the day store.

## Inertia first paint; JSON Actions for writes
First paint is Inertia. Mutating calls use Wayfinder on the Action with useHttp (JSON jsonResponse). Do not post Inertia forms for domain writes when asController JSON can serve. Do not call a hardcoded /api/v1 prefix.

## Terminal listens on the location channel
Shop-operable Terminal subscribes to the location channel only. CFD channel subscribe (and live customer/waiver patches) wait for the Device slice.

## Pinia and tickets hold all lines
Day store and CFD ticket payloads include every bike_reservation on a reservation. Components must not collapse a reservation to one bike or one remaining count.

## Location-channel day-store DTO
Location-channel DTOs (Inertia props, Echo broadcastWith, Action jsonResponse) are never Eloquent graphs.

Bike: id, bid, in_service, self_bookable, bike_situation_state, bike_situation_reservation_id, model (BikeModel name), variant (size), photo_url (bike then variant then model).

Line: id, product_id, product_label, bike_id, status (assigned/out/in), rider_name, rider_height_cm.

Reservation: id, stage (snake enum), starts_at, ends_at (ISO-8601), owed, paid, customer {id,name}, waiver_accepted_at, myrental_token, lines (every line).

Channel context on the Terminal page only: tenant_id, location_id, timezone, currency, return_situation.

Hydrate all bikes, plus reservations whose [starts_at, ends_at] intersects today in the location timezone, plus any reservation occupying a non-home bike. Echo patches by id; do not refetch the fleet.

## Terminal Echo is client-only
Do not call useEcho during SSR. useEcho instantiates Pusher in setup (not onMounted), which has no window on the server and remounts /prototype/terminal in a /broadcasting/auth loop. Subscribe from a child that mounts onMounted. Exclude prototype/terminal from Inertia SSR (HandleInertiaRequests $withoutSsr). Create Pinia inside withApp so SSR requests do not share a hydrated day store. configureEcho only when not import.meta.env.SSR.
