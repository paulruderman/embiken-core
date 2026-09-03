---
paths:
  - 'config/**'
---

# Config

## Provisional TTL is config not Location
Provisional cart TTL minutes live in config('embiken.provisional_ttl_minutes') default 15 (EMBIKEN_PROVISIONAL_TTL_MINUTES). Do not put TTL minutes on Location. Reservations still store expires_at.

## Database cache stays on the central connection
Database cache and the database queue stay on tenancy.database.central_connection (the named sqlite/mysql connection). Stancl switches the default connection to tenant after identification; do not leave DB_CACHE_CONNECTION or DB_QUEUE_CONNECTION empty. Do not enable CacheTenancyBootstrapper with the database cache driver (it requires tags). cache:table and migrate write the cache table on the central schema only; do not put cache in tenant migrations. Sessions stay on the default connection so they land in the shop database.

## Do not tenant-prefix Vite and Filament assets
Keep tenancy.filesystem.asset_helper_tenancy false. Vite, Filament, and Inertia CSS/JS are app assets, not tenant storage. asset() must not rewrite to /tenancy/assets. Enable ViteBundler so built @vite URLs use global_asset. Shop-uploaded photos still use tenant disks. Restart npm run dev after Vite server.host/hmr changes so public/hot is http://localhost:5173 instead of [::1].
