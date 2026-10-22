---
paths:
  - packages/bike-rentals/src/Services/Availability.php
---

# Src Services

## Allocation does not start physical preparation
Adding a specific bike to a Reservation reserves it without changing its physical situation or occupying Reservation. Preparation is an explicit staff step; the walk-in Action may explicitly start it. Unassign uses the reservation and fleet locks, and only homes a bike when that Reservation owns its physical situation.
