---
paths:
  - 'app/Observers/**'
---

# Observers

## Observers after commit, no occupancy
Observers are fan-out only. Implement ShouldHandleEventsAfterCommit. Register with #[ObservedBy]. Do not call Availability or perform checkout from an observer.

## No stage-to-bike sync in observers
Do not copy Reservation.stage onto Bike situation or bike_reservation. Do not heal stale Out rows from an observer; that belongs in the Action about to use the bike.

## Heal is Availability, not observers
Do not heal stale Out rows from an observer. Healing belongs in mutating Availability (allocate, swap, assign) or the scheduled tick, not quoteOccupancy. Do not heal an Out line on a Cancelled reservation from allocate or the tick.
