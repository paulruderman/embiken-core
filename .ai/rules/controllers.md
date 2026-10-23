---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## No thin controllers when an Action can asController
Use Actions for application use cases with domain behavior, authorization, validation, mutation, or meaningful orchestration; expose those through asController instead of a parallel thin controller. Do not make an Action just to serve a URL or render static/editorial content. Plain page rendering belongs in the normal page route/controller, and managed public copy belongs in the MarketingPage CMS. Inertia or GET alone does not make a page an Action. SPA and device writes call the same Action as JSON (jsonResponse), not a second controller.
