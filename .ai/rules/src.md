---
paths:
  - 'packages/bike-rentals/src/**'
---

# Src

## Terminal availability honors turnaround buffers
Book and Terminal enforce the effective turnaround buffer by default. Counter and Manager may exceptionally override reservation-time conflicts after an extreme warning, but overrides never shorten another Reservation automatically and never bypass pickup's physical availability checks.
