---
paths:
  - 'app/Http/Resources/**'
---

# Resources

## Day-store Resource DTO shape
LocationBikeResource, LocationLineResource, LocationReservationResource, and DayPatchResource are the shared day-store DTO. $wrap is null on DayPatchResource. Never dump Eloquent graphs (no bike_model_variant_id dump). Same shape for Inertia first paint, Echo, and Action jsonResponse.
