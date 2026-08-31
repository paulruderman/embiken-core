# Availability module interface

Status: claimed
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
