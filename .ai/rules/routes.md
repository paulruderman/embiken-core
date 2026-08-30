---
paths:
  - 'routes/**'
---

# Routes

## Central vs tenant hosts
Identify tenants by domain or subdomain. Central routes stay on the apex host. Tenant Inertia surfaces and shop Filament share the tenant host and differ by path. Location is request context, not a host.

## Surface paths
GET / 302-redirects to /book. Book is /book, MyRental /myrental, Station /station, CFD /cfd, Pad /pad, Screen /screen, shop Filament /manage. Do not add a kiosk path.

## Custom domains and ops tenant path
New tenants get a subdomain; custom domains are added later as Domain rows. Apex /t/{tenant}/… is ops-only (platform auth or local) and clones tenant surface paths. Do not register /t/ as a customer surface. Wayfinder for Book, MyRental, Station, Cfd, Pad, and Screen stays unprefixed on the tenant host.
