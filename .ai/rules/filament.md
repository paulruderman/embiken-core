---
paths:
  - 'app/Filament/**'
---

# Filament

## Two Filament panels, no HasTenants
Platform Filament on the central domain manages tenants and billing. Creating a Tenant provisions the tenant DB, one Location (empty hours), and a Manager (email plus set-password invite; do not type a Staff password on the tenant form), and starts Express onboarding. Shop Filament invites Counter and more Managers the same way. Shop Filament empty states (catalog, packages, hours) tell the Manager to create initial records. Do not seed demo bikes or shop hours. Shop Filament at /manage never captures Connect or cash. Do not use Filament HasTenants.

## Manager-only shop panel, no DB drop
Only Manager staff access /manage. Counter staff do not. Do not add a Devices resource or pairing codes until the Device slice. Platform Filament must not drop the tenant database when deleting a Tenant.

## Owed/paid reminder in Filament, still not a register
If a Reservation or Customer is viewed in Filament, show the same owed vs paid reminder. Reservation may write stage and owed via Actions. Managers may refund via RefundAction. Still never take Connect charges or cash checkout in Shop Filament. Platform billing is not rental Connect.

## Shop Filament configures catalog layers and packages
Shop Filament at /manage configures BikeCategory, BikeModel (optional photo, optional padding minutes), BikeModelVariant (optional photo), Bike (required bid, optional photo, in_service, self_bookable), and RentalPackage. Display photo falls back bike → variant → model. Do not add serial, barcode, RFID, or QR fields. Do not add an Allocation resource. Still never checkout.

## Packages configure Book confirm threshold
Shop Filament configures RentalPackage including which are Book-visible and what payment Book needs to leave Provisional (none through full). Still never checkout.

## Package deposit is cents or percent
A package deposit is configured as fixed cents or as a percent of owed, one or the other. Still never checkout.

## Filament edits package-variant rates
Shop Filament attaches variants to a RentalPackage with rate_cents. That list is both the price list and which products the offer includes. Still never checkout.

## Damage is Filament notes, not money
Damage is notes on the bike and/or reservation in Shop Filament. Do not add owed, transactions, or occupancy from damage. Still never checkout.

## Filament pairs CFD to Terminal (later)
When the Device slice lands, creating or editing a CFD Device sets paired_terminal_device_id. Still never checkout. Do not add this resource in shop-operable Filament.

## Shop Filament v1 resource list and Location hours
v1 Shop Filament resources: Location (name, timezone, currency default usd, one or more open intervals per weekday with optional next-day close, optional all-day closed dates, minimum turnaround buffer minutes default 10, bike-assignment policy, return situation home or back), BikeCategory, BikeModel (optional padding minutes, optional photo), BikeModelVariant (optional photo), Bike (required bid, optional photo, in_service, self_bookable), RentalPackage (including variant rates), Staff (invite set-password; Manager / Counter), blocking service, Reservation (writable stage, owed, interval, and lines via Actions), Customer (name, email, phone; not unique; read-only owed/paid reminder). Do not add Devices, Allocation, or add-ons. Hours and the shop buffer minimum are on Location, not a separate resource and not config-only. New Location hours start empty. Filament interval edits may ignore hours and the buffer (same as Terminal); they must not overlap another reservation's [starts_at, ends_at]. Filament still never captures Connect or cash checkout. RefundAction is allowed for Managers. Reservation writes must invoke domain Actions, not Eloquent in the resource. CancelAction may run from Filament and must prompt if any line is Out; pickup, return, and put-away stay off Filament.

## Subscription Stripe is not the 3% rental fee
Platform billing (shop subscription) is not the 3% rental application fee. Shop Filament does not edit Stripe Prices for rentals. Onboarding a connected account is platform or shop settings, not checkout.

## Filament onboards Express, not Standard
Shop or platform Filament starts Express onboarding (Account Links). Do not offer Connect Standard. Do not store shop sk_live keys. Still never checkout rentals in Filament.

## Filament buttons invoke domain Actions
Where a Manager can reasonably trigger a domain Action from a panel, add a Filament action button that calls the same Action as a callable. Do not reimplement handle() in the resource or save occupancy via Eloquent. Pickup, return, put-away, and Connect or cash checkout stay off Filament. RefundAction may appear for Managers.

## Location hours intervals and return situation
Shop Filament Location edits multiple open intervals per weekday (optional next-day close), closed dates, and return situation home or back. Put-away stays off Filament.

## Empty hours on tenant create
New Location hours start empty. Do not seed shop hours or a demo catalog. Book is not-configured until hours and a Book-visible package exist. Platform sets Location timezone (and currency) at tenant create.

## Staff invite and catalog photos
Platform tenant create takes a Manager email and sends a set-password invite. Do not type a Staff password on that form. Shop Filament invites Counter and additional Managers the same way. Bike resource requires bid; optional photos on Bike, variant, and model.

## No Devices resource until Device slice
Shop Filament v1 does not include a Devices resource or pairing codes. CFD paired_terminal_device_id waits. Manager-only /manage and no DB drop on tenant delete stay.

## Platform Users, Suspend, delete, Platform Manager
Platform Filament: User resource (invite set-password, disable not delete, one admin class; first User is Artisan/seeder). Tenant: Domain rows after create, Express status and retry Account Link, Suspend/unsuspend (padlocks Book, Terminal, /manage for Staff), Tenant delete only while suspended without dropping the DB (no restore). Tenant create also inserts a Platform Manager Staff row (locked label Platform; shop cannot edit/disable/delete). Impersonation starts from Platform Filament, lands on the tenant host as that Platform Manager, Exit back to the apex. Do not HasTenants. Do not impersonate a real Manager. Shop Filament still never checkout/pickup/return.

## Filament Reservation editor is per-line occupancy
Manager Reservation editor lists every line with bid, situation, and occupying Reservation. Flag a bike whose occupying Reservation is not the record being edited. Do not show a party as one bike.

## Two Filament panels by host and path
Platform panel id is platform at /platform on central domains (web User). Shop panel id is shop at /manage on the tenant host (staff guard, Manager only via canAccessPanel). Do not use HasTenants. Initialize shop tenancy with stancl domain middleware, not Filament tenancy.

## Shop Filament Location and Reservation write rules
Shop Location resource cannot create or delete (v1 is one Location). Reservation create/delete are off; interval, stage, owed, lines, cancel, and refund are Filament buttons that invoke domain Actions. Pickup, return, put-away, Connect, and cash stay off Filament.
