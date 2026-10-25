---
paths:
  - 'packages/bike-rentals/src/**'
---

# Src

## Terminal availability honors turnaround buffers
Book and Terminal enforce the effective turnaround buffer by default. Counter and Manager may exceptionally override reservation-time conflicts after an extreme warning, but overrides never shorten another Reservation automatically and never bypass pickup's physical availability checks.

## Overrides do not bypass pickup readiness
Counter and Manager may explicitly confirm extreme overrides for reservation-time conflicts and bike-level hard blocks; do not require a reason. Overrides cannot change the bike's actual service or situation state. Pickup requires the bike to be in service, free of blocking service, and physically ready for that Reservation.
