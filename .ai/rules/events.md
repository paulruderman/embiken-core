---
paths:
  - 'app/Events/**'
---

# Events

## Broadcast on tenant-prefixed location channels
Broadcast occupancy and reservation changes on private channel tenant.{tenantId}.location.{locationId} after commit. Authorize staff and devices on that channel. Do not use public unprefixed channels.

## Reverb and dedicated occupancy broadcasts
Target Reverb and Echo. ShouldBroadcastNow for shop-floor occupancy; always ShouldBroadcastAfterCommit. broadcastAs() a short Echo name. broadcastWith() ids, status, occupancy snapshots — never Eloquent graphs. Model CRUD uses BroadcastsEvents; occupancy snapshots and SwapAsset use dedicated ShouldBroadcast classes.
