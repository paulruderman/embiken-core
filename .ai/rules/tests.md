---
paths:
  - 'tests/**'
---

# Tests

## Tests use SQLite memory databases
Feature tests use SQLite :memory: for the central database and tenant database. Central tests do not query tenant models. Do not share one tenant DB across the whole suite. Production uses PostgreSQL.

## Occupancy tests only call Availability
Occupancy tests call quoteOccupancy, allocate, swapCandidates, swapAsset, and release. Assert remaining, OccupancyUnavailable reason+line, and bike_reservation rows those writes produce. Do not add Bike::available(), occupancy SQL, a second Availability adapter, or hours/owed assertions through Availability.
