---
paths:
  - 'packages/**/src/Filament/**'
---

# Src Filament

## Show Reservation context and readiness separately
When a bike is shown in a Reservation context, display its occupying Reservation only when it differs from the Reservation being viewed; without a specific Reservation context, show it for every non-home situation. Never flag a bike as wrong merely because its situation belongs to another Reservation. During preparation, a bike is ready for the target Reservation only when its situation is linked to that Reservation and Availability plus physical-readiness checks pass; another Reservation's staged/prepping state is not ready for this one.

## Configure Reservation readiness window on Location
Expose the upcoming-Reservation readiness warning window in Shop Filament Location settings. Default it to 45 minutes; staff can also see readiness while actively preparing a Reservation. Keep readiness separate from whether a different Reservation currently owns the bike's situation.
