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
One rental ticket: one Customer, one interval, one optional RentalPackage, and one or more Lines.
_Avoid_: Booking, order, cart (Provisional is a stage), a one-bike header

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
A shop ticket on one Bike. A non-resolved, non-cancelled ticket with `blocks_usage` occupies that bike. Parking the bike (`in_service` false) is a separate Staff flag.
_Avoid_: Down situation, service block row, maintenance schedule (not in this family)

**ServiceStage**:
The work state of a service request: open, in progress, blocked, resolved, or cancelled. Blocked means work cannot proceed, not that the bike is occupied.
_Avoid_: Workflow, mixing stage with `blocks_usage`

