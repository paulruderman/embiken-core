---
paths:
  - 'packages/**'
---

# Packages

## Path packages own their Filament and register a TenantModule
Each embiken/* path package has a ServiceProvider plus a {Name}Module. Filament for a model stays in that package. Fleet must not Composer-require rentals. dependencies() is string package IDs only. Staff, Location, viewscreens, and websocket channel classes stay in the host app. Actions in packages still use asController/asCommand.
