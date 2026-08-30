---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## No thin controllers when an Action can asController
Do not add Http\Controllers for a use case that can be an Action asController. Inertia page GETs that only load a surface are Actions too when they can be (htmlResponse). SPA and device writes call the same Action as JSON (jsonResponse), not a second controller.
