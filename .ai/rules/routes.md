---
paths:
  - 'routes/**'
---

# Routes

## Central vs tenant hosts
Identify tenants by domain or subdomain. Central routes stay on the apex host. Tenant Inertia surfaces and shop Filament share the tenant host and differ by path. Location is request context, not a host.

## Surface paths
GET / 302-redirects to /book. Book is /book, MyRental /myrental, Terminal /terminal, CFD /cfd, Screen /screen, shop Filament /manage. Do not add a kiosk or pad path.

## Custom domains and ops tenant path
New tenants get a subdomain; custom domains are added later as Domain rows. Apex /t/{tenant}/… is ops-only (platform auth or local) and clones tenant surface paths. Do not register /t/ as a customer surface. Wayfinder for Book, MyRental, Terminal, Cfd, and Screen stays unprefixed on the tenant host.

## One staff surface: /terminal
Staff session is Terminal at /terminal. Do not require a Device or pairing route. Do not add /station, /pad, /term, or a kiosk path. CFD paired_terminal_device_id waits for the Device slice.

## Register Action classes as routes and commands
Register tenant, page, and JSON Action routes to Action::class (invokable). Use Actions::registerRoutes and Actions::registerCommands. Wayfinder imports from the Action class. Do not wrap Actions in Http\Controllers. Do not add a public /api/v1 partner prefix in v1.

## Terminal route is staff session
Terminal at /terminal is a Staff session. Do not add Device pairing routes. CFD paired_terminal_device_id waits.

## Impersonation uses tenant host; Suspend padlocks shop routes
Impersonation is a Staff session on the tenant host (/manage, /terminal), not apex /t/. Suspended Tenants refuse Staff sign-in on Book, Terminal, and /manage. Custom Domain rows remain platform-editable. Do not register /t/ as a shop-operable User path.
