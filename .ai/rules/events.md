---
paths:
  - 'app/Events/**'
---

# Events

## Broadcast on tenant-prefixed location channels
Broadcast occupancy and reservation changes on private channel tenant.{tenantId}.location.{locationId} after commit. Authorize staff and terminal and screen devices on that channel. CFD tickets go to tenant.{tenantId}.cfd.{cfdDeviceId}; authorize only that CFD device. Do not use public unprefixed channels. Do not put CFD tickets on the location channel.

## Reverb and dedicated occupancy broadcasts
Target Reverb and Echo. ShouldBroadcastNow for shop-floor occupancy; always ShouldBroadcastAfterCommit. broadcastAs() a short Echo name. broadcastWith() DTOs — ids, statuses, day's bikes and reservations or a counter ticket — never Eloquent graphs. Model CRUD uses BroadcastsEvents; occupancy/day snapshots, counter tickets, and SwapAsset use dedicated ShouldBroadcast classes.

## Location DTOs may include name and money
Location-channel reservation DTOs may include display name, owed, and paid. Still never Eloquent graphs. CFD channel stays the counter ticket only. Do not put Book on either channel.
