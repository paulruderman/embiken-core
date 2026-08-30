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
Tenant schema includes reservations (starts_at, ends_at, stage, owed, paid), bike_reservation (product_id, nullable bike_id, Assigned/Out/In, optional check timestamps), bikes (situation state and bike_situation_reservation_id), and transactions belonging to reservations. owed and paid are caches, not a second ledger.

## Catalog and package tables, no products or allocations
Tenant schema includes bike_categories, bike_models, bike_model_variants, bikes (in_service, self_bookable), and rental_packages. reservations.rental_package_id is nullable. bike_reservation.product_id FKs to bike_model_variants. Do not create products or allocations tables.

## Buffer on bike_models; blocking service window
bike_models include turnaround buffer minutes. Tenant schema includes blocking service on a bike with optional starts_at/ends_at; Availability overlaps that window. Do not add a second occupancy table for maintenance schedules.

## rental_package_id nullable for Terminal hand quotes
reservations.rental_package_id is nullable. Book always writes it. Terminal hand quotes may leave it null. Do not make the column required at the database if Terminal omits it.

## package-variant rate pivot table
Tenant schema includes a rental_package_product (or equivalent) pivot: rental_package_id, product_id (FK to bike_model_variants), rate_cents. Unique (rental_package_id, product_id). Do not put rate columns on bike_model_variants or a single product price on rental_packages.

## CFD paired_terminal_device_id on devices
devices include optional paired_terminal_device_id for CFD rows (FK to devices). Do not put paired_terminal_device_id on Screen devices.
