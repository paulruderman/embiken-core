---
paths:
  - 'app/Observers/**'
---

# Observers

## Observers after commit, no occupancy
Observers are fan-out only. Implement ShouldHandleEventsAfterCommit. Register with #[ObservedBy]. Do not call Availability or perform checkout from an observer.
