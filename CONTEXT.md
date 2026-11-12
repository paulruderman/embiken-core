# Embiken

SaaS for independent bike rental shops. One **Tenant** is one shop operator; a **Location** is a store in that shop’s database.

## People

**User**:
A platform operator on the central database. Not Staff.
_Avoid_: landlord, ops user, admin account

**Staff**:
A shop worker in the tenant database. Manager or Counter, except the Platform Manager.
_Avoid_: User, employee, operator

**Manager**:
Staff who may use `/manage` and `/terminal`.
_Avoid_: admin, owner (the shop’s invitible first Manager is still a Manager)

**Counter**:
Staff who may use `/terminal` only.
_Avoid_: cashier, clerk

**Platform Manager**:
A Manager Staff row that is not a person. The only Staff a User may impersonate.
_Avoid_: ghost, synthetic operator, impersonation user, ops Staff

**Customer**:
The one renter on a Reservation. Extra people in the party are extra Lines, not extra Customers.
_Avoid_: guest (the Book browse state), client, account, rider (as a record)

## Shop

**Tenant**:
One shop operator. Identified by domain or subdomain. Not a Location.
_Avoid_: account, shop (when you mean the Tenant row), organization

**Location**:
A store in the tenant database. v1: exactly one per Tenant.
_Avoid_: tenant, station, branch as a second tenancy

**Domain**:
A host name row for a Tenant (subdomain at create; custom hosts added later).
_Avoid_: URL, site

## Tenant lifecycle

**Suspend**:
A Tenant state that padlocks the shop host for Staff.
_Avoid_: disable (that verb is for Users), pause, archive

**Tenant delete**:
Removing the central Tenant and Domain rows without dropping the shop database.
_Avoid_: deprovision, drop tenant (dropping the DB is forbidden)

**Impersonation**:
The one User-to-Staff crossing: the User is that tenant’s Platform Manager on the tenant host.
_Avoid_: ops `/t/`, HasTenants, “open shop” as a User session

## Reservation

**Reservation**:
One rental: one Customer, one interval, one optional RentalPackage, and one or more Lines.
Preferred term going forward; **Booking** is also a valid synonym and appears in legacy versions.
_Avoid_: order, cart (Provisional is a stage), a one-bike header

**Line**:
One `bike_reservation` row: one variant (class or assigned Bike) on that Reservation. Quantity is N rows. Optional Terminal nickname (`rider_name`) and `rider_height_cm` describe the person on that line, not a Customer.
_Avoid_: Rider model, qty, item, a product_id on the Reservation header

## Occupancy

**Availability**:
Whether bikes can be held for a proposed interval. The occupancy seam.
_Avoid_: Allocation, inventory, ledger, available-scope

**Occupancy**:
A hold on a Line for a Reservation interval (`[starts_at, ends_at]` inclusive) plus the turnaround buffer after `ends_at`.
_Avoid_: ledger, allocation pool

**Service request**:
A maintenance request for one Bike. A non-resolved, non-cancelled request with `blocks_usage` occupies that bike. Parking the bike (`in_service` false) is a separate Staff flag.
_Avoid_: Down situation, service block row, maintenance schedule (not in this family)

**Ticket**:
A maintenance/service request for a Bike. In conversation and user-facing language, “ticket” means this request, never a rental Reservation.
_Avoid_: Reservation, rental, booking

**ServiceStage**:
The work state of a service request: open, in progress, blocked, resolved, or cancelled. Blocked means work cannot proceed, not that the bike is occupied.
_Avoid_: Workflow, mixing stage with `blocks_usage`

**Service entry**:
A record of work performed for one ServiceRequest.
_Avoid_: maintenance schedule

**Part**:
A tenant-wide workshop catalog record selected when recording a part used on a service entry; each usage keeps a name, quantity, and unit snapshot.
_Avoid_: installed component, inventory item

**Installed component**:
A particular item fitted to a Bike whose installation and removal history may matter, such as a battery or motor.
_Avoid_: Part (the workshop catalog record)

**Module**:
An installed Composer tenant plugin under `packages/` (`embiken/*`). Enablement is per Tenant.
_Avoid_: package as a RentalPackage, addon, Laravel package in general

**Viewfeed**:
A named display a Viewscreen can show (core catalog plus enabled modules).
_Avoid_: wallboard as the type, page, route

**Viewscreen**:
A paired display that picks a Viewfeed by slug.
_Avoid_: Screen (the shop-floor surface), monitor, TV app

**Weather Site**:
An observation point with coordinates and an NWS grid. Not a Location.
_Avoid_: weather location, Location as a forecast point

**Weather Period**:
One stored hourly forecast interval for a Weather Site.
_Avoid_: conditions row, raw NWS dump

