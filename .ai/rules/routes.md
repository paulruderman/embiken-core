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
Staff plus device is Terminal at /terminal. Do not add /station, /pad, /term, or a kiosk path. CFD pairs to a terminal device via paired_terminal_device_id.
