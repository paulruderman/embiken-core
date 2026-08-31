# Availability is occupancy, not hours or money

Occupancy has to live in one module so Book, Terminal, Filament, and tests do not reimplement overlap (Brew scattered it across Bike scopes, observers, and booking helpers). Shop hours never allocate: a closed instant is not a bike being busy. `owed` is a quote of money, not of fit.

We put conflict, buffer, `in_service`, Book `self_bookable`, blocking service, and package-pivot membership behind `App\Services\Availability`. Book and MyRental Actions check hours; `quoteOccupancy` does not. `quoteOccupancy` returns per-line remaining after filling the asked cart so Book can browse (“3 left”); `allocate` / `swapAsset` throw `OccupancyUnavailable`. `allocate` *ensures* lines for a proposed interval (insert or keep, never duplicate) so extend is the same method with a new `ends_at`, not a sixth verb. Persist-a-bike is `allocate` with `bike_id`; there is no `assign()`. Swap ranking is `swapCandidates`; auto-pick vs picker is desk UX.

## Considered Options

- Hours inside Book `quoteOccupancy` only — rejected: same module, different rules per method; fights “hours never allocate”
- Split `bookAllocate` / `terminalAllocate` — rejected: doubles the surface; channel is one argument
- `quoteOccupancy` throws `sold_out` — rejected: Book cannot show remaining
- `Availability.assign()` — rejected: occupy-a-bike is `allocate`; display-only assign is not occupancy
- Auto-pick inside `swapAsset` — rejected: Terminal UX is not occupancy
- A fake Availability adapter in tests — rejected: one implementation; tests cross the real seam
