---
paths:
  - 'app/Filament/**'
---

# Filament

## Two Filament panels, no HasTenants
Platform Filament on the central domain manages tenants and billing. Shop Filament at /manage is configuration only and never performs checkout. Do not use Filament HasTenants.

## Manager-only shop panel, device pairing, no DB drop
Only Manager staff access /manage. Counter staff do not. Creating a Device issues a one-time pairing code; do not display a pasteable long-lived token. Platform Filament must not drop the tenant database when deleting a Tenant.
