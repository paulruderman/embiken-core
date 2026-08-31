# Availability module interface

Status: resolved
Type: grilling

## Question

What is the **interface** of the Availability **module** (`App\Services\Availability`) for the specs to name — the surface callers and tests both use — not its SQL?

Law already: `quoteOccupancy`, `allocate`, `swapAsset`, `release`; assign heals under row lock; `quoteOccupancy` never writes; Book honors buffer + hours; Terminal may ignore buffer but not overlap `[starts_at, ends_at]`; Cancelled does not occupy the class; Out on Cancelled is not healed; No Show occupies through ends_at + buffer.

Still to lock for a spec:

- Error modes (sold out, hours, buffer, blocking service, not `in_service`, not `self_bookable` on Book, package pivot miss) — one error type vs many; what Book vs Terminal must show
- Whether **assign** is a method on Availability or an Action that calls `allocate`
- Swap’s three scopes: does the module auto-pick the first fit, or return candidates for staff to pick (picker UX may wait on the Terminal prototype; the **interface** can still return candidates)
- What a test must not reach past (no occupancy scopes on Bike; no ledger)

Use codebase-design terms: module, interface, seam, depth. Do not design Filament or Vue.

## Answer

**Seam (in):** overlap of `[starts_at, ends_at]` inclusive, effective turnaround buffer, `in_service`, Book `self_bookable`, blocking service, package pivot when `package_id` is set, class slot vs pinned `bike_id`, Cancelled / No Show occupancy, stale Out (quote treats unavailable; writes heal except Cancelled Out). **Out of the module:** hours (Book / MyRental Action validation — amendment: `quoteOccupancy` does not refuse closed hours), `owed` / meters / confirm threshold, situation, damage. Pivot miss stays in.

**Methods.** `quoteOccupancy` / `allocate` take: proposed `starts_at` / `ends_at` (never implied from the model), channel `Book` | `Terminal` (Filament and desk pass Terminal; MyRental extend passes Book; never infer from auth), optional reservation id (exclude that reservation’s class occupancy; its assigned bikes stay held for its own lines; new carts pass null), `package_id` nullable (null = skip pivot), and the full cart of lines `{ id?: bike_reservation id, product_id, bike_id? }` — one physical unit per line, no qty. `release(lines)` drops occupancy for Assigned / never-Out lines. `swapCandidates(line)` / `swapAsset(line, bike)` always use Terminal occupancy (no channel); return / accept ids and ranked groups (same variant, same model, any in-service), not Eloquent graphs. Auto-pick vs picker is SwapAssetAction / Terminal UX.

**`quoteOccupancy`:** never writes, never locks, never throws for a full shop. Returns per-line `remaining` after filling this cart in line order (class: int; pinned bike: `0`|`1`). Throws `not_on_package` only. Book catalog = each variant once.

**`allocate`:** ensure these units occupy this interval inside the caller’s `DB::transaction()` (Availability does not open one). Missing line id → insert `bike_reservation`. Existing id → keep (update `bike_id` when pinning); do not duplicate. Same ids + new interval = extend; Action writes `ends_at`. Heal stale Out under row lock except Cancelled Out. No `Availability.assign()`: persist a bike is `allocate(..., bike_id)`; display-only assign never calls Availability.

**`release`:** Assigned lines only. Passing Out is `InvalidArgumentException`, not `OccupancyUnavailable`. Action writes Reservation header, stage, customer, money.

**Errors.** One `OccupancyUnavailable` (`ShouldntReport`) with a closed reason plus the failing line’s `product_id` and `bike_id`. Class: `sold_out`, `not_on_package`. Pinned bike: `occupied`, `not_in_service`, `blocking_service`, `not_self_bookable` (Book), `cancelled_out`, `not_on_package`. Buffer folds into `sold_out` / `occupied`. Thrown by `allocate` / `swapAsset` (and `quoteOccupancy` only for `not_on_package`). A stale quote then a failed allocate is expected.

**Tests** call only those methods. Assert `remaining`, `OccupancyUnavailable`, and the `bike_reservation` rows writes produce. No `Bike::available()`, occupancy SQL, second Availability adapter, or hours/`owed` through this module. Factories and the tenant Location are fine.

Glossary: `CONTEXT.md`. Rationale: [ADR-0002](../../../docs/adr/0002-availability-module-interface.md).
