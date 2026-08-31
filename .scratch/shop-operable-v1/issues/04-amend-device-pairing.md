# Amend Device pairing out of shop-operable Terminal

Status: resolved
Type: task

## Question

Write the **already-made** amendment into Boost law: shop-operable v1 Terminal is **staff session only**; Device pairing, Sanctum device tokens, CFD pairing, and the Shop Filament Devices resource wait for a later map.

Do not re-grill. Apply via `.ai/guidelines/embiken.md` + `php artisan boost:update --no-discover --no-interaction`, and path-scoped `record-rule` for files that currently require a bound device (especially `.ai/rules/pages.md`, `.ai/rules/events.md`, `.ai/rules/filament.md`, `.ai/rules/http.md`, `.ai/rules/models.md`).

Must stay true: Manager vs Counter; Terminal still `/terminal`; location channel still exists; Screen/CFD still out of this family; later Device work should restore staff+device without a second rewrite of occupancy.

Record what changed (paths + gist) in the answer. This unblocks Terminal specs from contradicting Boost.

## Answer

Shop-operable Terminal is **staff password session only**. Device pairing, Sanctum device tokens, CFD `paired_terminal_device_id`, and Shop Filament Devices wait. Occupancy and `tenant.{tenantId}.location.{locationId}` are unchanged; that channel authorizes **staff**. When Device returns, Terminal becomes staff plus device without a second occupancy rewrite.

**Guidelines:** `.ai/guidelines/embiken.md` (Terminal Who, Shop Filament, auth, location-channel auth) then `php artisan boost:update --no-discover --no-interaction`.

**Path-scoped (rewritten + `record-rule`):** pages, events, filament, http, models, routes, migrations, actions. Hand-edited also: js, terminal. Two extra `record-rule` calls (js, terminal) were blocked; those files already carry the same law.

Manager vs Counter, `/terminal`, and the location channel remain. CFD/Screen stay later surfaces and stay device-only when built.
