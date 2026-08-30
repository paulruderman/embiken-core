---
paths:
  - 'app/Events/**'
---

# Events

## Broadcast on tenant-prefixed location channels
Broadcast occupancy and reservation changes on private channel tenant.{tenantId}.location.{locationId} after commit. Authorize staff and terminal and screen devices on that channel. CFD tickets go to tenant.{tenantId}.cfd.{cfdDeviceId}; authorize that CFD device and the paired Terminal device. Do not use public unprefixed channels. Do not put CFD tickets on the location channel.

## Reverb and dedicated occupancy broadcasts
Target Reverb and Echo. ShouldBroadcastNow for shop-floor occupancy; always ShouldBroadcastAfterCommit. broadcastAs() a short Echo name. broadcastWith() DTOs — ids, statuses, day's bikes and reservations or a counter ticket — never Eloquent graphs. Model CRUD uses BroadcastsEvents; occupancy/day snapshots, counter tickets, and SwapAsset use dedicated ShouldBroadcast classes.

## Location DTOs may include name and money
Location-channel reservation DTOs may include display name, owed, and paid. Bike DTOs include bid. Still never Eloquent graphs. CFD channel stays the counter ticket only (focused reservation: lines, package, owed/paid, interval, customer name/email/phone, waiver checkbox). Customer-field patches are live both ways with the paired Terminal. Do not put Book on either channel. Do not put other rentals on the CFD ticket. Stored signature capture is later.

## CFD ticket includes live customer fields
CFD ticket DTOs include customer name/email/phone and waiver checkbox. Authorize the paired Terminal on that channel. Do not put a signature blob on the channel in v1.

## Bike DTOs include bid
Location-channel bike DTOs include bid. Screen may render bid. Still never customer PII or money on Screen.
