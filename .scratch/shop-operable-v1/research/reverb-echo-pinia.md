# Reverb + Echo + Pinia for a staff-session Terminal day store

Researched against Laravel 13.x broadcasting / Reverb docs, Inertia.js v3 official docs, and Pinia official docs. This repo is Laravel 13.29 with Inertia Vue `@inertiajs/vue3` 3.7. Pinia is not installed yet.

**Question:** How should Laravel Reverb + Echo + Pinia hydrate and patch a shop-floor day store in an Inertia Vue 3 app, for a staff-session Terminal (no Screen, no CFD, no Sanctum device token)?

**Out of scope:** this file does not invent the location-channel DTO field list (still fog). It only records payload *conventions* official docs actually specify.

---

## Recommended shape (from the docs, not from Embiken DTOs)

1. **First paint** is an Inertia HTML response whose embedded page object already contains the Terminal snapshot (bikes + today’s reservations). Copy those props into a Pinia store. No second HTTP “hydrate” call is required. ([Inertia protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol); [Inertia pages](https://inertiajs.com/docs/v3/the-basics/pages))
2. **Install Pinia once** on the Vue app (`createPinia()` + `app.use(pinia)`). The store is global and is not bound to the page-component tree, so it survives Inertia visits that do not reboot Vue. ([Pinia getting started](https://pinia.vuejs.org/getting-started.html); [Inertia how it works](https://inertiajs.com/docs/v3/core-concepts/how-it-works))
3. **`configureEcho({ broadcaster: 'reverb' })` once** at app boot. Subscribe with `useEcho` from `@laravel/echo-vue` on the private channel name (without the `private-` prefix). Put `useEcho` on a **persistent Terminal layout** so the subscription is not torn down on every intra-Terminal page swap. ([Laravel broadcasting — client Reverb](https://laravel.com/docs/13.x/broadcasting#client-reverb); [useEcho](https://laravel.com/docs/13.x/broadcasting#using-react-or-vue); [Inertia persistent layouts](https://inertiajs.com/docs/v3/the-basics/layouts))
4. **Authorize the channel with the staff session**, not a Sanctum device token: default `/broadcasting/auth` under `web` middleware, `Broadcast::channel(..., ['guards' => ['staff']])`. Do not follow Sanctum’s `/api/broadcasting/auth` + `auth:sanctum` + custom Echo `authorizer` path. ([Authorizing channels](https://laravel.com/docs/13.x/broadcasting#authorizing-channels); [Sanctum contrast](https://laravel.com/docs/13.x/sanctum#authorizing-private-broadcast-channels))
5. **Patch Pinia by id** from Echo callbacks using `$patch` (object or function form). Broadcast dedicated occupancy events with `broadcastAs()` + `broadcastWith()` arrays that include identifiers; do not serialize Eloquent graphs as the payload. ([Pinia `$patch`](https://pinia.vuejs.org/core-concepts/state.html); [broadcastAs / broadcastWith](https://laravel.com/docs/13.x/broadcasting#broadcast-name))
6. **Occupancy timing:** `ShouldBroadcastNow` for the sync queue; wait for DB commit with Laravel 13’s `ShouldDispatchAfterCommit` (there is no `ShouldBroadcastAfterCommit` in this framework). ([ShouldBroadcastNow](https://laravel.com/docs/13.x/broadcasting#broadcast-queue); [broadcasting and database transactions](https://laravel.com/docs/13.x/broadcasting#broadcasting-and-database-transactions))

---

## 1. Echo auth: staff session, not a device token

### What Echo does

Private channels require an authenticated, authorized user. Echo automatically issues an HTTP request with the channel name so Laravel can decide whether the current user may listen. ([Laravel broadcasting — authorizing channels](https://laravel.com/docs/13.x/broadcasting#authorizing-channels))

When broadcasting is installed, Laravel registers **`/broadcasting/auth`** to handle those requests (via `channels:` on `withRouting` in `bootstrap/app.php`). ([same](https://laravel.com/docs/13.x/broadcasting#authorizing-channels))

Framework default for that route group: **`web` middleware** (session cookie), and CSRF verification is **disabled** on `/broadcasting/auth`:

```85:91:vendor/laravel/framework/src/Illuminate/Broadcasting/BroadcastManager.php
        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->match(
                ['get', 'post'], '/broadcasting/auth',
                '\\'.BroadcastController::class.'@authenticate'
            )->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
```

So a same-origin Terminal that already has a staff session cookie can authorize without a Sanctum bearer token and without Echo sending `X-XSRF-TOKEN` on that particular POST. (Inertia’s own mutating XHR still uses the `XSRF-TOKEN` cookie → `X-XSRF-TOKEN` header. That is a different request path. ([Inertia CSRF](https://inertiajs.com/docs/v3/security/csrf-protection)))

### Channel callback + staff guard

`Broadcast::channel` takes the channel name and a callback that returns `true`/`false`. The first argument is the currently authenticated user; `{wildcards}` become subsequent arguments. ([Defining authorization callbacks](https://laravel.com/docs/13.x/broadcasting#defining-authorization-callbacks))

**Default guard only.** Private channels authenticate via the application’s **default** authentication guard. If that user is missing, the callback never runs. To use a non-default staff guard, pass `guards`:

```php
Broadcast::channel('tenant.{tenantId}.location.{locationId}', function ($staff, string $tenantId, string $locationId) {
    // return true only if this staff may hear this shop floor
}, ['guards' => ['staff']]);
```

([Authorization callback authentication](https://laravel.com/docs/13.x/broadcasting#defining-authorization-callbacks))

Echo `private('tenant.'.$tenantId.'.location.'.$locationId)` / `useEcho(\`tenant.${tenantId}.location.${locationId}\`, …)` — do **not** include a `private-` prefix; Echo adds that. ([Listening for events](https://laravel.com/docs/13.x/broadcasting#listening-for-events); [useEcho](https://laravel.com/docs/13.x/broadcasting#using-react-or-vue))

Reverb speaks the Pusher protocol; `configureEcho({ broadcaster: 'reverb' })` is the documented Vue client. The `reverb` Echo broadcaster requires laravel-echo v1.16.0+. ([Client-side Reverb](https://laravel.com/docs/13.x/broadcasting#client-reverb); [Reverb introduction](https://laravel.com/docs/13.x/reverb))

### Contrast: Sanctum device / SPA token path (do not use for this Terminal)

Sanctum’s “authorizing private broadcast channels” recipe is a different stack: drop `channels` from `withRouting`, call `withBroadcasting(..., ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']])`, and give Echo a custom Pusher `authorizer` that POSTs to **`/api/broadcasting/auth`**. ([Sanctum](https://laravel.com/docs/13.x/sanctum#authorizing-private-broadcast-channels))

That is the documented path for Sanctum-authenticated SPA/API clients (and would be the later Device-token path). Staff-session Terminal should keep the default `/broadcasting/auth` + `web` + `guards: ['staff']`.

Sanctum also states API tokens belong in an `Authorization` header, and that you should **not** use API tokens to authenticate your own first-party SPA — cookie session is the first-party path. ([Sanctum how it works](https://laravel.com/docs/13.x/sanctum#how-it-works); [API tokens](https://laravel.com/docs/13.x/sanctum#api-token-authentication))

---

## 2. First-paint Inertia props can seed Pinia (no second HTTP)

### Inertia already ships the snapshot

The first browser request is a normal HTML document. A `<script type="application/json" data-page="app">` element holds the JSON page object, including `props`. Inertia boots Vue from that object. Subsequent Inertia visits return JSON `{ component, props, url, version }` and swap the page component **without a full reload**. ([Protocol — HTML responses](https://inertiajs.com/docs/v3/core-concepts/the-protocol); [How it works](https://inertiajs.com/docs/v3/core-concepts/how-it-works))

Pages receive controller data as Vue props. Official wording: retrieve just the data necessary for that page — **no API required**; all data needed can be retrieved before the page is rendered, so you need not show a loading state for the initial visit. ([Pages](https://inertiajs.com/docs/v3/the-basics/pages))

Pass the day snapshot as **page props** on the Terminal Inertia response (`Inertia::render('Terminal/…', [ … ])`), not as globally shared data. Shared data is merged into **every** Inertia response and “should be used sparingly”. ([Responses](https://inertiajs.com/docs/v3/the-basics/responses); [Shared data](https://inertiajs.com/docs/v3/data-props/shared-data))

Do **not** use once-props for occupancy: once-props are resolved once and remembered across navigations, which would freeze a stale fleet. ([Once props](https://inertiajs.com/docs/v3/data-props/once-props), via shared-data docs)

Inertia `useRemember` / history state is for local component/form state on back/forward, not a realtime store. ([Remembering state](https://inertiajs.com/docs/v3/data-props/remembering-state))

### Pinia is the place to keep it after first paint

`createPinia()` creates the root store; `app.use(pinia)` installs it. A store “isn’t bound to your Component tree” and is “a bit like a component that is always there”. Use it for data that “needs to be preserved through pages”. ([Getting started](https://pinia.vuejs.org/getting-started.html))

Inertia v3 Vue registers plugins in `createInertiaApp({ withApp(app) { app.use(pinia) } })`. ([Client-side setup — customizing the app](https://inertiajs.com/docs/v3/installation/client-side-setup))

Hydrate by writing the Inertia props into the store (direct assignment or `$patch`). `$patch` accepts a partial object or a function for collection updates (push/replace-by-id) as a single Devtools entry. Assigning `store.$state = …` internally calls `$patch`. ([State — mutating / replacing](https://pinia.vuejs.org/core-concepts/state.html))

Components should read the store (keep reactivity: do not destructure state without `storeToRefs`). ([Defining a store](https://pinia.vuejs.org/core-concepts/))

**Consequence not named by Pinia/Inertia together, but implied by both:** if you `$patch` from page props inside a **page** `setup()`, every Inertia visit remounts that page and will overwrite Echo patches with the server snapshot from that response. If you hydrate + `useEcho` inside a **persistent layout**, layout `setup()` runs once per layout lifetime: first paint seeds the store, Echo patches it, intra-Terminal visits do not remount the listener. Persistent layouts exist specifically so layout state survives visits (docs’ example: an audio player that should keep playing). ([Persistent layouts](https://inertiajs.com/docs/v3/the-basics/layouts))

A later Terminal GET that you *want* as a full resync can still `$patch` from that visit’s props.

---

## 3. Broadcast shape so Pinia can patch by id

Official docs do **not** define Embiken’s DTO fields. They do define naming, payload control, and an id-bearing example.

### Channel

`broadcastOn()` returns `PrivateChannel` (or an array). Example: `new PrivateChannel('orders.'.$this->order->id)`. ([Defining broadcast events](https://laravel.com/docs/13.x/broadcasting#defining-broadcast-events))

For this app the recorded name is `tenant.{tenantId}.location.{locationId}` — that is product law, not a Laravel convention. Laravel only requires the string you authorize in `routes/channels.php` to match the `PrivateChannel` name.

### Event names (what Echo listens for)

| Source | Wire name Echo should listen for |
| --- | --- |
| Default `ShouldBroadcast` class `App\Events\Foo` | `Foo` (Echo prepends `App.Events.`) ([Namespaces](https://laravel.com/docs/13.x/broadcasting#namespaces)) |
| `broadcastAs(): string` returns `'server.created'` | **`.server.created`** — leading `.` so Echo does not prepend the namespace ([Broadcast name](https://laravel.com/docs/13.x/broadcasting#broadcast-name)) |
| `BroadcastsEvents` model update on `Reservation` | **`.ReservationUpdated`** — leading `.` because these are not `App\Events` classes. Payload default: `{ "model": { "id": …, … }, "socket": "…" }` ([Model broadcasting conventions](https://laravel.com/docs/13.x/broadcasting#model-broadcasting-conventions); [Listening for model broadcasts](https://laravel.com/docs/13.x/broadcasting#listening-for-model-broadcasts)) |

`useEcho` takes a string or **array of event names**, and a typed payload generic. It **leaves the channel on unmount**; it also returns `{ leaveChannel, leave, stopListening, listen }` for manual control. ([Using React, Vue, or Svelte](https://laravel.com/docs/13.x/broadcasting#using-react-or-vue))

Recorded law’s “listen `.ReservationUpdated`” matches Laravel’s model-broadcast convention. Dedicated occupancy classes should `broadcastAs()` a short name and listen with a leading dot.

### Payload: ids, not Eloquent graphs

**Default:** every **public** property of the event is serialized. A public Eloquent `$user` becomes a full model array in JSON. ([Broadcast data](https://laravel.com/docs/13.x/broadcasting#broadcast-data))

**Control:** `broadcastWith(): array` replaces that. Laravel’s own example is an identifier map:

```php
public function broadcastWith(): array
{
    return ['id' => $this->user->id];
}
```

([same](https://laravel.com/docs/13.x/broadcasting#broadcast-data))

Models using `BroadcastsEvents` may also define `broadcastWith(string $event)` to avoid dumping `$this` (the default `['model' => $this]` is an Eloquent graph). ([Model broadcasting conventions](https://laravel.com/docs/13.x/broadcasting#model-broadcasting-conventions))

Pinia can then `$patch((state) => { … find by id … })`. The docs do not require a specific field list beyond “include the identifiers you will key on”.

`useEcho` / `listen` callbacks receive that JSON as `e`. Example: `console.log(e.order)` when the public property was `$order`. ([Listening for event broadcasts](https://laravel.com/docs/13.x/broadcasting#listening-for-event-broadcasts))

### When the message is sent

- **`ShouldBroadcast`:** queued on the default queue. Docs: run a queue worker; broadcasting is done via queued jobs. ([Quickstart next steps](https://laravel.com/docs/13.x/broadcasting#quickstart))
- **`ShouldBroadcastNow`:** uses the **sync** queue instead of the default queue driver — broadcast in-process, no worker wait. ([Broadcast queue](https://laravel.com/docs/13.x/broadcasting#broadcast-queue))
- **After commit (Laravel 13 name):** if `after_commit` on the queue connection is `false`, implement **`Illuminate\Contracts\Events\ShouldDispatchAfterCommit`** on the event so it is not dispatched until open DB transactions commit (discarded on rollback; immediate if no transaction). ([Broadcasting and database transactions](https://laravel.com/docs/13.x/broadcasting#broadcasting-and-database-transactions); [Events — dispatching after transactions](https://laravel.com/docs/13.x/events#dispatching-events-after-database-transactions))

**`ShouldBroadcastAfterCommit` does not exist** in Laravel 13.29 (`vendor/laravel/framework` has `ShouldDispatchAfterCommit` only). Occupancy events that must be both immediate and post-commit implement **both** `ShouldBroadcastNow` and `ShouldDispatchAfterCommit`.

Queue `after_commit => true` also delays queued broadcast events until commit. ([Queues — jobs and database transactions](https://laravel.com/docs/13.x/queues#jobs-and-database-transactions))

### `toOthers` vs a Pinia-as-truth store

`broadcast(…)->toOthers()` skips the **current socket** (the connection whose `X-Socket-ID` header was sent on the HTTP request that triggered the broadcast). Docs assume a **global Axios instance** auto-sets that header; otherwise set `X-Socket-ID` from `Echo.socketId()`. ([Only to others](https://laravel.com/docs/13.x/broadcasting#only-to-others))

Inertia 3 **removed Axios**; it uses a built-in XHR client. Interceptors exist on `http.onRequest`. `useHttp` is a plain JSON call and does not run the Inertia page lifecycle. ([Client-side setup — HTTP client](https://inertiajs.com/docs/v3/installation/client-side-setup); [HTTP requests](https://inertiajs.com/docs/v3/the-basics/http-requests))

So: **do not rely on Axios auto-`X-Socket-ID`**. If occupancy Echo is the Pinia source of truth, either skip `toOthers` (every tab including the actor gets the patch) or attach `Echo.socketId()` on mutating `useHttp` **and** apply the Action JSON to Pinia yourself for the acting tab.

---

## 4. SPA gotchas

### Echo stays connected across Inertia visits

Inertia visits swap the page component and history; they do **not** reboot the Vue app. ([How it works](https://inertiajs.com/docs/v3/core-concepts/how-it-works))

`configureEcho` lives in the JS entry (docs: `resources/js/app.js`). That Echo instance outlives Inertia visits. ([Client-side Reverb](https://laravel.com/docs/13.x/broadcasting#client-reverb))

`useEcho` **leaves the channel when the consuming component unmounts**. A Terminal **page** that unmounts on every Inertia visit will unsubscribe. A **persistent layout** does not unmount on those visits. ([useEcho](https://laravel.com/docs/13.x/broadcasting#using-react-or-vue); [Persistent layouts](https://inertiajs.com/docs/v3/the-basics/layouts))

Manual leave: `Echo.leaveChannel(name)` or `Echo.leave(name)` (also leaves associated private/presence). `stopListening` drops a listener without leaving. Same methods are returned from `useEcho`. ([Leaving a channel](https://laravel.com/docs/13.x/broadcasting#leaving-a-channel))

Leaving Terminal entirely (full page load to Filament `/manage`, or a layout that does not wrap Terminal) tears down Vue and the socket. That is expected.

Wrapping a layout as a child inside the page **does** destroy it every visit — do not subscribe there. ([Layouts](https://inertiajs.com/docs/v3/the-basics/layouts))

Duplicate listeners: if you both `configureEcho` globally **and** call `Echo.private().listen` in a page without leaving, visits stack listeners. Prefer one `useEcho` in the persistent layout.

### Multiple Terminal tabs

Official Pinia/Inertia/Echo docs do **not** provide a cross-tab Pinia bus. Pinia state is in-memory on **that** Vue app.

Each tab: own first-paint hydrate, own Echo connection, own `/broadcasting/auth` (same session cookie). Reverb delivers to every subscribed connection on the channel. ([Reverb — scaling](https://laravel.com/docs/13.x/reverb) describes fan-out to connections; conceptually each socket is independent.)

`toOthers` excludes **one** socket id, not “all tabs of this staff member”. Other tabs still receive the event. ([Only to others](https://laravel.com/docs/13.x/broadcasting#only-to-others))

Tabs opened after a mutation hydrate from a later Inertia GET (server snapshot). They do not inherit the other tab’s Pinia.

Presence channels (`joining` / `leaving` / `here`) are the documented way to see *who* is subscribed, not to sync Pinia. This Terminal question does not require presence. ([Presence channels](https://laravel.com/docs/13.x/broadcasting#presence-channels))

### History size

Inertia stores page responses in history state. Very large day-store props on every Terminal visit can hit browser limits (Firefox ~16 MiB). Keep the snapshot to what the floor needs; Echo patches after that. ([Maximum response size](https://inertiajs.com/docs/v3/the-basics/responses))

### Reverb origins

Reverb rejects WebSocket clients whose origin is not in `allowed_origins` (or `*`). Terminal’s shop host must be allowed. ([Reverb — allowed origins](https://laravel.com/docs/13.x/reverb#configuration))

---

## 5. What official docs do not settle

- Location-channel DTO field list (ids/statuses/money/PII). Fog by design.
- Whether occupancy is one snapshot event vs many small `broadcastWith` patches.
- Whether `BroadcastsEvents` on Reservation/Bike is enough vs dedicated `ShouldBroadcastNow` classes (docs support both; they differ in payload shape).
- Pinia persist plugins / `BroadcastChannel` / localStorage sync across tabs — not in Pinia core docs cited here.
- Echo `authEndpoint` option — documented in older Laravel Echo install snippets; **13.x broadcasting** only documents automatic auth against `/broadcasting/auth`. Stick to that default for staff session.

---

## Sources

- [Laravel 13 broadcasting](https://laravel.com/docs/13.x/broadcasting) (Echo, Reverb client, auth, `broadcastAs`/`broadcastWith`, `ShouldBroadcastNow`, `ShouldDispatchAfterCommit`, `useEcho`, model broadcasts, `toOthers`)
- [Laravel 13 Reverb](https://laravel.com/docs/13.x/reverb)
- [Laravel 13 events — after commit](https://laravel.com/docs/13.x/events#dispatching-events-after-database-transactions)
- [Laravel 13 queues — after_commit](https://laravel.com/docs/13.x/queues#jobs-and-database-transactions)
- [Laravel 13 Sanctum — private broadcast channels](https://laravel.com/docs/13.x/sanctum#authorizing-private-broadcast-channels)
- Laravel 13.29 `Illuminate\Broadcasting\BroadcastManager::routes()` (default `web` + CSRF off on `/broadcasting/auth`)
- [Inertia v3 protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol), [how it works](https://inertiajs.com/docs/v3/core-concepts/how-it-works), [pages](https://inertiajs.com/docs/v3/the-basics/pages), [responses](https://inertiajs.com/docs/v3/the-basics/responses), [layouts](https://inertiajs.com/docs/v3/the-basics/layouts), [shared data](https://inertiajs.com/docs/v3/data-props/shared-data), [client-side setup](https://inertiajs.com/docs/v3/installation/client-side-setup), [CSRF](https://inertiajs.com/docs/v3/security/csrf-protection), [HTTP / useHttp](https://inertiajs.com/docs/v3/the-basics/http-requests)
- [Pinia getting started](https://pinia.vuejs.org/getting-started.html), [defining a store](https://pinia.vuejs.org/core-concepts/), [state / `$patch`](https://pinia.vuejs.org/core-concepts/state.html)
