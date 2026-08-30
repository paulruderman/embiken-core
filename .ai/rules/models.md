---
paths:
  - 'app/Models/**'
---

# Models

## Tenant is a shop, Location is in-tenant
A stancl Tenant is one shop operator. Location is a store in the tenant database, never a stancl tenant. Do not put tenant_id on tenant models. Never query tenant models on the central connection.

## Reservation product required, asset nullable
A reservation always has product_id. asset_id is nullable until assigned. Class booking leaves asset null; specific-bike booking sets asset and still stores the product. Do not add a parallel rentals table as a second source of truth.

## Eloquent model events and BroadcastsEvents
Use $dispatchesEvents or #[ObservedBy] for lifecycle fan-out. Surfaces that live-update use BroadcastsEvents on PrivateChannel tenant.{tenantId}.location.{locationId} with a compact payload. Model events must not allocate occupancy or replace Actions.

## Database-per-tenant, one Location, Staff and Customer
Each Tenant has its own database, created when the Tenant is created. Do not drop tenant databases on tenant delete. v1: exactly one Location per tenant; no location switcher. Staff and Customer are separate Authenticatable models. Device is not Authenticatable; Sanctum tokens belong on Device.
