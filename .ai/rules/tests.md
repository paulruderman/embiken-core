---
paths:
  - 'tests/**'
---

# Tests

## Tenant tests use SQLite memory databases
Tenant feature tests initialize tenancy with SQLite :memory: as the tenant database. Central tests do not query tenant models. Do not share one tenant DB across the whole suite.

## Occupancy tests only call Availability
Occupancy tests call quoteOccupancy, allocate, swapCandidates, swapAsset, and release. Assert remaining, OccupancyUnavailable reason+line, and bike_reservation rows those writes produce. Do not add Bike::available(), occupancy SQL, a second Availability adapter, or hours/owed assertions through Availability.
