---
paths:
  - 'resources/js/**'
---

# Js

## Echo with useEcho
configureEcho({ broadcaster: 'reverb' }) from @laravel/echo-vue. Subscribe with useEcho to tenant.{tenantId}.location.{locationId} using broadcastAs names. Leave on unmount. The screen consumes a compact occupancy snapshot; do not refetch the full fleet.

## Leading-dot model broadcast names
Model broadcasts are not App\Events classes. Listen with a leading-dot name: useEcho(..., '.ReservationUpdated').
