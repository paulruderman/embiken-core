---
paths:
  - 'app/Actions/**'
---

# Actions

## Invokable domain Actions for writes
Every mutating use case is an Action under app/Actions/{Domain}/, named *Action. Use App\Actions\Action and AsAction. Invoke as a callable, never ->handle(). Method-inject the Action. handle() takes typed arguments, not Request. Authorize and validate in HTTP. Wrap multi-model writes in DB::transaction() inside handle(). Do not ShouldQueue the Action.

## No broadcast in Actions
Do not ShouldBroadcast or fire broadcast events from handle(). Do not dispatch the same lifecycle event as $dispatchesEvents or BroadcastsEvents.

## Availability is the only occupancy seam
Book, Station, and Pad Actions must call Availability (allocate, quoteOccupancy, swapAsset, release). Do not count occupancy in the Action. Pickup and return live on Station and Pad. Swap is SwapAssetAction in one transaction; do not cancel and rebook.
