---
paths:
  - 'packages/**'
---

# Packages

## Path packages own their Filament and register a TenantModule
Each embiken/* path package has a ServiceProvider plus a {Name}Module. Filament for a package model stays in that package. Fleet must not Composer-require rentals. dependencies() is string package IDs only. Staff, Location, Device, Display, and websocket channel classes stay in the host app. Actions in packages still use asController/asCommand.

## Modules contribute Wall Display Feeds
TenantModules may provide code-owned Feed definitions through feeds(); the host app owns Device and Display models. Managers assign registered Feeds to Displays.
