---
paths:
  - 'resources/js/**'
---

# Js

## Echo with useEcho
configureEcho({ broadcaster: 'reverb' }) from @laravel/echo-vue. Shop-floor surfaces subscribe with useEcho: Screen/Terminal to tenant.{tenantId}.location.{locationId}; CFD to tenant.{tenantId}.cfd.{cfdDeviceId}. Leave on unmount. First paint hydrates a shared Pinia store with the day's bikes and reservations. Echo handlers patch that store. Components read Pinia as the reactive source of truth. Do not refetch the full fleet on each event. Do not use a compact counts-only snapshot as the Screen's store.

## Leading-dot model broadcast names
Model broadcasts are not App\Events classes. Listen with a leading-dot name: useEcho(..., '.ReservationUpdated').

## Pinia is shop-floor realtime truth
Use a shared Pinia store for shop-floor realtime state. Hydrate on first paint; patch from Echo; components read the store. Do not keep a second local copy of bikes or reservations in a page. Pinia is not in package.json yet; add it when this is implemented, not a different client store.

## Day-store window, PII on wire, Screen must not show it
Pinia day store is Terminal and Screen only. Hydrate all bikes, plus reservations whose [starts_at, ends_at] intersects today in the location timezone, plus any reservation that currently has a bike not in situation home. Location payload may include customer display name and owed/paid. Screen UI must not show customer PII or money; Terminal may. CFD uses a separate ticket Pinia store for the paired Terminal's focused reservation (including Terminal-driven data/waiver prompts). Book and MyRental do not hydrate the day store.

## Inertia first paint; JSON Actions for writes
First paint is Inertia. Mutating calls use Wayfinder on the Action with useHttp (JSON jsonResponse). Do not post Inertia forms for domain writes when asController JSON can serve. Do not call a hardcoded /api/v1 prefix.
