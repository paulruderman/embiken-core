---
paths:
  - 'database/migrations/**'
---

# Migrations

## Tenant migrations, portable schema
Tenant tables live in database/migrations/tenant. Schema must build on every Laravel-supported database: portable types and indexes, no engine-specific DDL.

## Tenant DB provisioned, never auto-dropped
Tenant schema lives in database/migrations/tenant and is applied when a Tenant is created. Do not add migrations or hooks that drop tenant databases on tenant delete.

## Core tenant tables for reservations and money
Tenant schema includes reservations (starts_at, ends_at, stage, owed, paid, optional waiver_accepted_at), bike_reservation (product_id, nullable bike_id, Assigned/Out/In, optional check timestamps), bikes (situation state, bike_situation_reservation_id, required unique bid), customers (required name, email, phone; indexed, not unique), and transactions belonging to reservations. owed and paid are caches, not a second ledger.

## Catalog and package tables, no products or allocations
Tenant schema includes bike_categories, bike_models, bike_model_variants, bikes (required unique bid, in_service, self_bookable, situation), and rental_packages. Optional photo on bike_models, bike_model_variants, and bikes. Do not add serial, barcode, RFID, or QR columns in v1. reservations.rental_package_id is nullable. bike_reservation.product_id FKs to bike_model_variants. Do not create products, allocations, or add-ons tables.

## Buffer on locations and bike_models; blocking service window
locations include currency (default usd), a minimum turnaround buffer minutes (default 10), a bike-assignment policy (terminal, book_may_pin, pickup_only), and return situation (home or back; default home). Open hours are one or more intervals per weekday; an interval may close on the next calendar day. Optional all-day closed dates. Do not seed hours on tenant create. Do not add tax columns in v1. bike_models include optional padding minutes; effective buffer is max(location minimum, model padding). Tenant schema includes blocking service on a bike with optional starts_at/ends_at; Availability overlaps that window. Do not add a second occupancy table for maintenance schedules. Do not store buffer on bikes or reservations.

## rental_package_id nullable for Terminal hand quotes
reservations.rental_package_id is nullable. Book always writes it. Terminal hand quotes may leave it null. Do not make the column required at the database if Terminal omits it.

## package-variant rate pivot table
Tenant schema includes a rental_package_product (or equivalent) pivot: rental_package_id, product_id (FK to bike_model_variants), rate_cents. Unique (rental_package_id, product_id). Do not put rate columns on bike_model_variants or a single product price on rental_packages.

## CFD paired_terminal_device_id on devices (later)
When the Device slice lands, devices include optional paired_terminal_device_id for CFD rows (FK to devices). Do not put paired_terminal_device_id on Screen devices. Do not create a devices table in shop-operable schema.

## Hours intervals, customer indexes, waiver timestamp
locations store return situation and one or more open intervals per weekday (close may be next calendar day). customers email and phone are indexed, not unique. reservations may have waiver_accepted_at. Do not store a signature blob in v1.

## Location currency; no tax or add-ons
locations include currency default usd. Do not add tax columns. Do not create add-ons tables. Do not seed hours rows on tenant create.

## bikes.bid unique; optional photos
bikes.bid is required and unique in the tenant. Optional photo on bikes, bike_model_variants, and bike_models. Do not add serial, barcode, RFID, or QR columns in v1.

## No devices table until Device slice
Do not create a devices table or paired_terminal_device_id in shop-operable tenant schema. Occupancy tables are unchanged.
