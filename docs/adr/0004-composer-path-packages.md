# Composer path packages and per-tenant module enablement

The shop domain will keep growing (fleet, rentals, StripeConnect, weather, BRM, Book). Leaving everything in `App\` would repeat Brew’s problem: optional integrations and core occupancy sit in one namespace, and frontend registration is hand-edited. Folders-only splits do not give Composer install graphs or per-tenant enablement.

We keep a host app for Staff, Location, viewscreens, and websockets, and put fleet and rentals in `packages/*` path repositories. Every `embiken/*` package is a tenant plugin (`tenants.modules`) except platform-scoped ones. Rentals Composer-requires fleet; `dependencies()` is string IDs only. Filament for a model lives in that model’s package. That is harder to reverse than namespaces, but it is the install/enablement seam we need and it is surprising without this record.

## Considered Options

- Folders under `app/Domain` only — rejected: no install graph, no per-tenant disable
- Brew-style Module class without ServiceProviders — rejected: Filament and Vite stayed hand-wired
- All domain packages always-on once installed — rejected: we want Tenant-level enablement, including fleet and rentals
