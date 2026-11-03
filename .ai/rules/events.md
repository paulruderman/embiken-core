---
paths:
  - 'app/Events/**'
---

# Events

## Broadcast on tenant-prefixed device channels
Broadcast occupancy and reservation changes on private channel tenant.{tenantId}.location.{locationId} after commit. Authorize Staff, paired Terminal Devices, and Wall Display Devices on that channel. Customer Display tickets go to tenant.{tenantId}.cfd.{deviceId}; authorize that Customer Display Device and its paired Terminal Device only. Do not use public unprefixed channels. Do not put Customer Display tickets on the location channel. Occupancy does not depend on Device.

## Reverb and dedicated occupancy broadcasts
Target Reverb and Echo. ShouldBroadcastNow for shop-floor occupancy; always ShouldDispatchAfterCommit. broadcastAs() a short Echo name. broadcastWith() DTOs — ids, statuses, day's bikes and reservations or a counter ticket — never Eloquent graphs. Model CRUD uses BroadcastsEvents; occupancy/day snapshots, counter tickets, and SwapAsset use dedicated ShouldBroadcast classes.

## Location DTOs may include name and money
Location-channel reservation DTOs may include display name, owed, and paid. Bike DTOs include bid. Still never Eloquent graphs. Customer Display channel stays the counter ticket only (focused reservation: lines, package, owed/paid, interval, customer name/email/phone, waiver checkbox). Customer-field patches are live both ways with the paired Terminal. Do not put Book on either channel. Do not put other rentals on the Customer Display ticket. Stored signature capture is later.

## Customer Display ticket includes live customer fields
Customer Display ticket DTOs include customer name/email/phone and waiver checkbox. Authorize only its paired Terminal on that channel. Do not put a signature blob on the channel in v1.

## Bike DTOs include bid
Location-channel bike DTOs include bid. Wall Display may render bid and situation, but never customer PII or money.

## Location channel authorizes staff and appropriate devices
Authorize tenant.{tenantId}.location.{locationId} for Staff, paired Terminal Devices, and Wall Display Devices. Customer Display Devices do not join the location channel. Occupancy broadcasts do not depend on Device.

## Occupancy uses ShouldDispatchAfterCommit
Shop-floor occupancy events implement ShouldBroadcastNow and Illuminate\Contracts\Events\ShouldDispatchAfterCommit. Laravel 13 has no ShouldBroadcastAfterCommit — do not use that name. ShouldBroadcastNow uses the sync queue; ShouldDispatchAfterCommit waits for the DB transaction to commit (discarded on rollback).

## Location occupancy broadcast DTOs
Location-channel occupancy uses dedicated ShouldBroadcastNow + ShouldDispatchAfterCommit events (LocationBikePatched, LocationReservationPatched), not Action handle(). broadcastAs is BikeCreated/Updated/Deleted and ReservationCreated/Updated/Deleted. broadcastWith is the Location* Resource DTO (deleted payload is {id} only). Channel is tenant.{tenantId}.location.{locationId}. Listen with leading-dot names. Line CRUD rebroadcasts the parent reservation DTO (all lines).
