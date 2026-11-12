---
paths:
  - 'routes/**'
---

# Routes

## Central vs tenant hosts
Identify tenants by domain or subdomain. Central routes stay on the apex host. Tenant Inertia surfaces and shop Filament share the tenant host and differ by path. Location is request context, not a host.

## Surface paths
GET / 302-redirects to /book. Book is /book, MyRental /myrental, Terminal /terminal, Customer Display /cfd, Wall Display /wall-display, shop Filament /manage. Devices pair on the tenant host. Do not add a kiosk or pad path.
MyRental /myrental accepts an email-only link request; the emailed reservation URL is a separately signed `/myrental/{reservation}/{token}` route and must retain tenant-host routing.

## Custom domains and ops tenant path
New tenants get a subdomain; custom domains are added later as Domain rows. Apex /t/{tenant}/… is ops-only (platform auth or local) and clones tenant surface paths. Do not register /t/ as a customer surface. Wayfinder for Book, MyRental, Terminal, Cfd, and Wall Display stays unprefixed on the tenant host.

## One staff surface: /terminal
Terminal at /terminal requires a Staff session and paired Terminal Device. Customer Display and Wall Display require paired Devices. Do not add /station, /pad, /term, or a kiosk path.

## Register Action classes as routes and commands
Register tenant, page, and JSON Action routes to Action::class (invokable). Use Actions::registerRoutes and Actions::registerCommands. Wayfinder imports from the Action class. Do not wrap Actions in Http\Controllers. Do not add a public /api/v1 partner prefix in v1.

## Device pairing is tenant-scoped
Pairing routes initialize tenancy on the tenant host. Never pair Devices across tenant boundaries.

## Impersonation uses tenant host; Suspend padlocks shop routes
Impersonation is a Staff session on the tenant host (/manage, /terminal), not apex /t/. Suspended Tenants refuse Staff sign-in on Book, Terminal, and /manage. Custom Domain rows remain platform-editable. Do not register /t/ as a shop-operable User path.

## Page routes versus use-case Actions
Static and editorial page GETs use the normal page route/controller structure. Register an Action as a route when it represents a real use case with domain behavior, authorization or validation, a state change, or meaningful orchestration; GET or Inertia rendering alone is not a reason to create an Action.

## Register Action classes as routes and commands
Register an Action route only for a meaningful application use case. Static or editorial page GETs use the normal page route/controller structure; do not wrap a page render in an Action. Wayfinder should be generated from the registered route/controller or Action that owns the behavior.
