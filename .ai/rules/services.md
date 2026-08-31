---
paths:
  - 'app/Services/**'
---

# Services

## Availability service is the occupancy seam
Occupancy lives in App\Services\Availability (quoteOccupancy, allocate, swapCandidates, swapAsset, release). Actions call it; do not duplicate overlap in Actions. Do not ShouldQueue Availability. Complex queries otherwise belong in Eloquent scopes — this service is the rare exception. Do not add a parallel occupancy service.

## Availability interface: channel, remaining, ensure
quoteOccupancy and allocate take proposed starts_at/ends_at, channel Book|Terminal, optional reservation id, nullable package_id, and a cart of unit lines {id?, product_id, bike_id?} — no qty. quoteOccupancy returns per-line remaining (never throws sold_out; throws not_on_package only). allocate ensures rows (insert or keep, never duplicate) inside the caller’s transaction; no assign(). swapCandidates + swapAsset always use Terminal occupancy. release is Assigned lines only (Out is InvalidArgumentException). OccupancyUnavailable (ShouldntReport) with closed reasons: class sold_out|not_on_package; pinned occupied|not_in_service|blocking_service|not_self_bookable|cancelled_out|not_on_package. Buffer folds into sold_out/occupied. Hours and owed stay out. Do not add Availability.assign() or a fake adapter.
