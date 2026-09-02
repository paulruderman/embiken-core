---
name: handoff
description: >-
  Creates Filament panels that allow for CRUD on Embiken Eloquent models while
  following shop-operable business logic. Use when the user invokes /handoff,
  asks to hand off Filament admin, or wants Platform/Shop Filament resources
  for Tenant, Location, catalog, Reservation, Staff, or related models.
disable-model-invocation: true
---

# Handoff

create filament panels that allow for crud on these models, hopefully following business logic.

## Before writing

1. Read `CONTEXT.md`, `.ai/guidelines/embiken.md`, `.ai/rules/filament.md`, and `.ai/rules/models.md`.
2. Read `.ai/rules/index.md` and every rule whose globs cover `app/Filament/**`.
3. Activate `laravel-best-practices`. Use Boost `search-docs` for **Filament 5** (this repo’s stack is `filament/filament` `^5.0`). Do not copy Filament 3 resource layout.
4. Confirm installed versions (`composer show filament/filament`). If Filament is missing, `composer require filament/filament:"^5.0"` then `php artisan filament:install --panels --no-interaction`. **Never** `--scaffold` (this app already has Vite + Inertia Vue).
5. Do **not** use Filament `HasTenants`. Tenancy is stancl database-per-tenant.

## Two panels

| Panel | Path | Guard / who | Connection |
| --- | --- | --- | --- |
| Platform | central host (not `/manage`) | `web` **User** | central |
| Shop | `/manage` | **Staff** Manager only | tenant (initialize tenancy by domain/subdomain) |

Counter Staff must not access `/manage`. Do not impersonate a real Manager; Platform impersonation is the Platform Manager Staff row only.

## What “CRUD” means here

Naive `Model::create` in a resource is allowed only for **configuration** records that law already treats as Filament forms (catalog, hours, packages, rates, Staff invite, Location, damage notes, service tickets).

**Not raw Eloquent save:**

- Occupancy (interval, lines, assign, swap, extend, cancel) — domain **Actions**; Filament buttons call the same Action. Must not overlap another reservation’s `[starts_at, ends_at]`. Filament may ignore shop hours and the turnaround buffer (same as Terminal).
- Money: show owed vs paid. Managers may **RefundAction**. Never Connect capture, cash, or other checkout in Filament.
- Pickup, return, put-away — **off Filament**.
- Tenant create — provision DB, one Location (timezone/currency, empty hours, 10-minute buffer), invitible Manager (email + set-password invite, no password on the form), Platform Manager Staff row, Express Account Link. Tenant delete only while suspended; **do not drop** the tenant database (`TenantDeleted` must not `DeleteDatabase`).

If the Action does not exist yet, **create the Action first** (lorisleiva: `asController` / `asCommand` / Filament button), then the resource. Do not invent a thin `Http\Controller` for the same use case.

## Platform resources

- **User** — invite set-password; disable (`disabled_at`), never delete; one admin class.
- **Tenant** — name, Suspend/unsuspend, Express status + retry Account Link, Domains (add/edit/remove after create).
- **Domain** — hostname rows for a Tenant.

No rental checkout. No shop catalog on the apex.

## Shop resources (v1)

Register these; nest the rest.

| Resource | Notes |
| --- | --- |
| Location | One per tenant; no location picker. Hours + closed dates on this resource (not a separate top-level resource). Buffer default 10. Assignment policy, return situation. Photos N/A. Empty hours until the Manager fills them. |
| BikeCategory, BikeModel, BikeModelVariant, Bike | Required catalog chain. Variant **size** required; height bands optional. Bike: unique **bid**, `in_service`, `self_bookable`, optional photo. Display photo bike → variant → model. No serial, barcode, RFID, QR. No Allocation. |
| RentalPackage | Meter, confirm threshold, deposit **cents or percent** (not both), min/max duration, `book_visible`, `sort_order`. Attach variants with `rate_cents` (membership + price). |
| Staff | Invite set-password; Manager / Counter. **Cannot** edit/disable/delete the Platform Manager (`is_platform_manager`). |
| Customer | name, email, phone; not unique. Read-only owed/paid reminder (from reservations). |
| ServiceRequest | `ServiceStage`, `blocks_usage`, optional window, description. Do not write `in_service` when opening/resolving a ticket. Nested **ServiceEntry**. No checklists/schedules. |
| Reservation | List **every** `bike_reservation` line (product, bid or unassigned, situation, occupying Reservation; flag wrong ticket). Writable stage, owed, interval, and lines **via Actions**. CancelAction must prompt if any line is Out. Optional `notes` / `damage_notes`. |

**Nested / not top-level:** `LocationHour`, `LocationClosedDate`, `RentalPackageProduct`, `BikeReservation`, `ServiceEntry`. **Transactions** — read-only on the Reservation (ledger), not a cash register.

**Out of this family:** Devices, CFD pairing, Allocation, add-ons, tax, Product table.

## Forms and enums

Use the PHP backed enums on the models (`ReservationStage`, `PackageMeter`, `ConfirmThreshold`, `ServiceStage`, `StaffRole`, `BikeAssignmentPolicy`, `ReturnSituation`, etc.). Do not introduce a second vocabulary (Booking, Plan, Workflow, Down).

`rider_name` / `rider_height_cm` are optional on lines (Terminal nickname / fitting). Screen must not show `rider_name`; Filament may.

## Tests and finish

- Tenant Filament tests initialize tenancy (SQLite `:memory:` tenant schema). Central tests must not query tenant models.
- `vendor/bin/pint --dirty --format agent` after PHP changes.
- Do not seed a demo fleet or shop hours.

## Law lives in the repo

Do not paste `.ai/guidelines/embiken.md` into resources. Follow it. When a durable Filament constraint is new, `record-rule` with glob `app/Filament/**`.
