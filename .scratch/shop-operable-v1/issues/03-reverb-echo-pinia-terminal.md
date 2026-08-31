# Reverb Echo Pinia for Terminal day store

Status: resolved
Type: research

## Question

How should **Laravel Reverb + Echo + Pinia** hydrate and patch a shop-floor day store in an **Inertia Vue 3** app, for Terminal only (no Screen, no CFD in this family)?

Recorded law: first paint hydrates every bike plus reservations that intersect today (shop timezone) and any reservation whose bikes are not `home`; Echo patches that store; channel `tenant.{tenantId}.location.{locationId}`; DTOs not Eloquent; `ShouldBroadcastNow` for occupancy; `ShouldBroadcastAfterCommit`; staff (not Device) will authorize the channel after the Device amendment.

Need from official Laravel Reverb, Echo, Pinia, and Inertia Vue 3 docs:

- Echo auth endpoint and private channel authorization pattern with a **session** (staff guard), not a Sanctum device token
- Whether the Inertia first-paint payload can seed Pinia without a second HTTP hydrate
- How broadcasts should look so a Pinia store can patch by id (event names, payload shape conventions) without inventing Embiken’s DTO field list
- SPA gotchas: Echo left connected across Inertia visits, multiple Terminal tabs

Write findings to `.scratch/shop-operable-v1/research/reverb-echo-pinia.md` with citations. Do not invent the location-channel DTO field list (still fog).

## Answer

Staff-session Terminal: default `/broadcasting/auth` (`web` middleware, CSRF off on that route) plus `Broadcast::channel(..., ['guards' => ['staff']])`. Do not use Sanctum’s `/api/broadcasting/auth` + device token. Inertia first-paint `props` can seed Pinia with no second HTTP call — install Pinia once, hydrate + `useEcho` on a **persistent Terminal layout** so Inertia visits do not drop the socket. Occupancy: `ShouldBroadcastNow` + Laravel 13’s `ShouldDispatchAfterCommit` (`ShouldBroadcastAfterCommit` is gone); `broadcastAs()` short names with a leading-dot Echo listen; `broadcastWith()` id-bearing arrays, not Eloquent graphs. Each tab has its own Pinia; Echo fans out to every subscribed socket.

Findings: [reverb-echo-pinia.md](../research/reverb-echo-pinia.md)
