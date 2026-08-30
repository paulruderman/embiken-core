---
paths:
  - 'app/Filament/**'
---

# Filament

## Two Filament panels, no HasTenants
Platform Filament on the central domain manages tenants and billing. Shop Filament at /manage never captures Connect or cash. Do not use Filament HasTenants.

## Manager-only shop panel, device pairing, no DB drop
Only Manager staff access /manage. Counter staff do not. Creating a Device issues a one-time pairing code; do not display a pasteable long-lived token. Platform Filament must not drop the tenant database when deleting a Tenant.

## Owed/paid reminder in Filament, still not a register
If a Reservation or Customer is viewed in Filament, show the same owed vs paid reminder. Reservation may write stage and owed via Actions. Managers may refund via RefundAction. Still never take Connect charges or cash checkout in Shop Filament. Platform billing is not rental Connect.

## Shop Filament configures catalog layers and packages
Shop Filament at /manage configures BikeCategory, BikeModel, BikeModelVariant, Bike (including in_service and self_bookable), and RentalPackage. Do not add an Allocation resource. Still never checkout.

## Packages configure Book confirm threshold
Shop Filament configures RentalPackage including which are Book-visible and what payment Book needs to leave Provisional (none through full). Still never checkout.

## Package deposit is cents or percent
A package deposit is configured as fixed cents or as a percent of owed, one or the other. Still never checkout.

## Filament edits package-variant rates
Shop Filament attaches variants to a RentalPackage with rate_cents. That list is both the price list and which products the offer includes. Still never checkout.

## Damage is Filament notes, not money
Damage is notes on the bike and/or reservation in Shop Filament. Do not add owed, transactions, or occupancy from damage. Still never checkout.

## Filament pairs CFD to Terminal
Creating or editing a CFD Device sets paired_terminal_device_id. Still never checkout.

## Shop Filament v1 resource list and Location hours
v1 Shop Filament resources: Location (name, timezone, weekly hours, optional closed dates, minimum turnaround buffer minutes default 10, bike-assignment policy), BikeCategory, BikeModel (optional padding minutes), BikeModelVariant, Bike, RentalPackage (including variant rates), Staff, Devices, blocking service, Reservation (writable stage, owed, interval, and lines via Actions), Customer (name, email, phone; read-only owed/paid reminder). Do not add Allocation. Hours and the shop buffer minimum are on Location, not a separate resource and not config-only. Filament interval edits may ignore hours and the buffer (same as Terminal); they must not overlap another reservation's [starts_at, ends_at]. Filament still never captures Connect or cash checkout. RefundAction is allowed for Managers. Reservation writes must invoke domain Actions, not Eloquent in the resource. CancelAction may run from Filament and must prompt if any line is Out; pickup and return stay off Filament.

## Subscription Stripe is not the 3% rental fee
Platform billing (shop subscription) is not the 3% rental application fee. Shop Filament does not edit Stripe Prices for rentals. Onboarding a connected account is platform or shop settings, not checkout.

## Filament onboards Express, not Standard
Shop or platform Filament starts Express onboarding (Account Links). Do not offer Connect Standard. Do not store shop sk_live keys. Still never checkout rentals in Filament.

## Filament buttons invoke domain Actions
Where a Manager can reasonably trigger a domain Action from a panel, add a Filament action button that calls the same Action as a callable. Do not reimplement handle() in the resource or save occupancy via Eloquent. Pickup, return, and Connect or cash checkout stay off Filament. RefundAction may appear for Managers.
