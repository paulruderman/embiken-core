# Stack packages for Laravel 13 (currently installable versions)

Researched 2026-08-31 against Packagist, npm, official docs, and GitHub release / `composer.json` constraints. **Did not run `composer require`.**

**Question:** Which currently installable versions of `stancl/tenancy`, Filament, `lorisleiva/laravel-actions`, Stripe PHP SDK and/or Cashier (only if Cashier is the supported Connect Express path), `laravel/reverb`, Pinia, Laravel Echo, and the Reverb Echo client work together on this repo’s PHP / Laravel / Inertia Vue 3 stack, and what would block us?

**Repo baseline (already installed):** PHP `^8.3`, `laravel/framework` `^13.17`, `inertiajs/inertia-laravel` `^3.0`, `laravel/sanctum` `^4.0`, `laravel/wayfinder` `^0.1.14`; Vue `^3.5.13`, `@inertiajs/vue3` `^3.0.0`, Tailwind CSS `^4.1.1`. `composer.json` has `"minimum-stability": "stable"`.

---

## Recommended version set

Install these ranges. They Composer/npm-resolve on Laravel 13 + PHP `^8.3` (including 8.5) without changing `minimum-stability`.

| Package | Constraint | Latest stable seen | Why this major |
| --- | --- | --- | --- |
| `stancl/tenancy` | `^3.10` | v3.10.1 (2026-08-05) | First 3.x line with Laravel 13; v4 is not a stable Packagist release |
| `filament/filament` | `^5.0` | v5.7.6 (2026-08-05) | Current stable; Laravel 13 + Livewire 4 + Tailwind v4.1 already in this repo |
| `livewire/livewire` | (pulled by Filament 5) | v4.4.2 | Must be **≥ 4.2.0** for Laravel 13; Filament 5’s `^4.1` resolves there today |
| `lorisleiva/laravel-actions` | `^2.12` | v2.12.0 (2026-08-25) | Laravel 13 since v2.10.0 |
| `stripe/stripe-php` | `^21.3` | v21.3.0 (2026-08-27) | Official Connect Account / Account Link / PaymentIntent SDK. **Do not add Cashier** for rental Connect |
| `laravel/reverb` | `^1.11` | v1.11.1 (2026-08-06) | First 1.x line with `illuminate/* ^13.0` |
| `pinia` | `^4.0` | 4.0.3 | Peer `vue: ^3.5.11`; this repo has Vue `^3.5.13` |
| `laravel-echo` | `^2.4` | 2.4.0 | Laravel 13 Vue recipe; Reverb broadcaster exists since Echo ≥ 1.16.0 |
| `pusher-js` | `^8.6` | 8.6.0 | Reverb speaks the Pusher protocol; required even though we are not using Pusher Cloud |
| `@laravel/echo-vue` | `^2.4` (optional) | 2.4.0 | Same version as `laravel-echo`; Vue 3 peer. Use if we want `useEcho` / `configureEcho` hooks |

**Do not install:** `laravel/cashier` for rental Connect Express. Cashier is subscription billing. Stable Cashier v16.7.0 also cannot take `stripe/stripe-php` 21 (`^17.4\|^18\|^19\|^20` only).

**Soft follow-up (not a hard Composer block):** Pinia 4 lists an optional TypeScript peer `>=5.6.0`. This repo has `typescript: ^5.2.2`. Bump TypeScript when adding Pinia 4 if we want that peer satisfied.

---

## 1. `stancl/tenancy` — use `^3.10`, not v4

### Installable versions

Packagist latest stable is **v3.10.1**. Its `composer.json` requires:

- `php: ^8.0`
- `illuminate/support: ^10.0|^11.0|^12.0|^13.0`
- `require-dev` `laravel/framework: ^10.0|^11.0|^12.0|^13.0`

([Packagist `stancl/tenancy`](https://packagist.org/packages/stancl/tenancy); [GitHub `v3.10.1` composer.json](https://raw.githubusercontent.com/archtechx/tenancy/v3.10.1/composer.json))

Laravel 13 landed on **v3.10.0** ([GitHub release v3.10.0](https://github.com/archtechx/tenancy/releases/tag/v3.10.0) — “Laravel 13 support” via PR #1444). **v3.10.1** backports Laravel 13.24 support ([GitHub release v3.10.1](https://github.com/archtechx/tenancy/releases/tag/v3.10.1)).

**v3.9.1** stops at `illuminate/support: ^10.0|^11.0|^12.0` — it will not resolve on Laravel 13.

PHP `^8.0` includes 8.3–8.5 (Composer `<9.0.0`). No upper PHP bound.

### Database-per-tenant + domain/subdomain

Official v3 docs: the package focuses on **multi-database** tenancy; identification middleware includes domain, subdomain, and combined domain-or-subdomain. ([Introduction](https://tenancyforlaravel.com/docs/v3/introduction); [Tenant identification](https://tenancyforlaravel.com/docs/v3/tenant-identification); [Quickstart](https://tenancyforlaravel.com/docs/v3/quickstart) — “multi-database tenancy & domain identification”)

Middleware names from those docs:

- `Stancl\Tenancy\Middleware\InitializeTenancyByDomain`
- `Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain`
- `Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain`

Quickstart tenant model uses `HasDatabase` + `HasDomains` and `TenantWithDatabase` — that is the database-per-tenant + hostname shape Embiken needs.

### v4 is not currently installable as stable

v4 docs tell you to install `composer require stancl/tenancy:dev-master`. ([v4 Getting started](https://v4.tenancyforlaravel.com/getting-started/)) Packagist’s published versions for this package have **no 4.x stable tag** (p2 metadata inspected 2026-08-31). This app’s `"minimum-stability": "stable"` would refuse `dev-master` without a stability change. **Stay on 3.10.**

---

## 2. Filament — v5 (current), not v3; v4 still Composer-legal

### What Packagist actually publishes

Latest stable majors on Packagist (2026-08-31):

| Major | Latest stable | Docs status | PHP | Laravel (composer) | Livewire | Tailwind (docs) |
| --- | --- | --- | --- | --- | --- | --- |
| **v5** | v5.7.6 | Current | `^8.2` | `illuminate/contracts: ^11.28\|^12.0\|^13.0` via `filament/support` | `^4.1` | v4.1+ |
| **v4** | v4.12.6 | “previous version” | `^8.2` | same `^11.28\|^12.0\|^13.0` | `^3.5` | v4.1+ |
| **v3** | v3.3.55 | Previous-previous | `^8.1` | `illuminate/*: ^10.45\|^11.0\|^12.0\|^13.0` on `filament/filament` | v3.0+ (docs) | not Tailwind 4 |

Sources:

- [Filament 5 installation](https://filamentphp.com/docs/5.x/introduction/installation) — PHP 8.2+, Laravel v11.28+, Tailwind CSS v4.1+; `composer require filament/filament:"^5.0"`
- [Filament 5 upgrade guide](https://filamentphp.com/docs/5.x/upgrade-guide) — also Livewire v4.0+
- [Filament 4 installation](https://filamentphp.com/docs/4.x/introduction/installation) — same PHP / Laravel / Tailwind floor; 4.x is labeled previous; `^4.0`
- [Filament 3 panel installation](https://filamentphp.com/docs/3.x/panels/installation) — PHP 8.1+, Laravel v10.0+, Livewire v3.0+
- [`filament/support` v5.7.6 composer.json](https://raw.githubusercontent.com/filamentphp/filament/v5.7.6/packages/support/composer.json)
- [`filament/support` v4.12.6 composer.json](https://raw.githubusercontent.com/filamentphp/filament/v4.12.6/packages/support/composer.json)
- Laravel 13 support PR merged 2026-03-17: [filamentphp/filament#19514](https://github.com/filamentphp/filament/pull/19514)

This repo already has Tailwind `^4.1.1`, which matches Filament 4/5 docs and **does not** match Filament 3’s documented stack (Livewire 3 / older Tailwind). Composer would still let Filament 3.3.55 onto Laravel 13, but it fights this starter kit. **Do not pick v3.**

### Livewire floor for Laravel 13

- Livewire **v4.1.4** still requires `illuminate/support: ^10.0|^11.0|^12.0` (no 13).
- Livewire **v4.2.0+** adds `^13.0` ([GitHub release v4.2.0](https://github.com/livewire/livewire/releases/tag/v4.2.0) includes “[3.x] Add Laravel 13 support”; [`composer.json` on v4.2.0](https://raw.githubusercontent.com/livewire/livewire/v4.2.0/composer.json) has `^10.0|^11.0|^12.0|^13.0`).
- Latest Livewire **v4.4.2** (Packagist, 2026-08-24): `php: ^8.1`, `illuminate/*: ^10.0|^11.0|^12.0|^13.0`. ([Packagist `livewire/livewire`](https://packagist.org/packages/livewire/livewire))
- Latest Livewire **v3.8.6** also has `illuminate/support: ^10.0|^11.0|^12.0|^13.0`, so Filament **v4** (`livewire: ^3.5`) can Composer-resolve on Laravel 13.

Filament 5’s `livewire/livewire: ^4.1` will currently resolve to 4.4.x. **Do not pin Livewire to 4.1.0–4.1.4** or Laravel 13 will fail.

### Why recommend v5 over v4

1. Official docs call 5.x current and 4.x previous.
2. Brew used Filament 5.
3. This repo already has Tailwind v4.1.
4. Both majors Composer-support Laravel 13; v5 is the one that will keep getting the Laravel 13 follow-through.

Install as a **panel builder**, not `--scaffold`. Filament 5 docs warn that `filament:install --scaffold` overwrites existing files and is only for new Laravel projects. This app already has Vite 8 + Inertia Vue + Tailwind 4. Use `composer require filament/filament:"^5.0"` then `php artisan filament:install --panels`. ([Installation](https://filamentphp.com/docs/5.x/introduction/installation))

Filament Livewire panels and the Inertia Vue SPA are separate UIs. They can coexist; do not import Filament’s CSS into the SPA entry unless a Filament Blade layout needs it.

---

## 3. Tenancy × Filament traps (`HasTenants` is forbidden here)

Filament’s built-in tenancy is **not** stancl database-per-tenant.

Official wording: Filament tenancy “implies that the user belongs to many tenants (organizations, teams, companies, etc.) and may switch between them.” Setup is `Panel::tenant(Team::class)` plus `Filament\Models\Contracts\HasTenants` on the authenticatable (`getTenants`, `canAccessTenant`). ([Filament 5 multi-tenancy](https://filamentphp.com/docs/5.x/users/tenancy); same model on [Filament 4](https://filamentphp.com/docs/4.x/users/tenancy), which is labeled previous)

That is single-app, often single-database, **team-switcher** tenancy with tenant IDs in the panel URL. Embiken tenancy is **stancl database-per-tenant identified by domain/subdomain**, one shop operator per tenant, v1 one Location, two Filament panels (platform on the apex, shop at `/manage`). `HasTenants` would add a tenant picker and a second tenancy model. **Do not implement `HasTenants`.**

What to do instead (from combining the two official models, not from a dedicated “Filament + stancl” page — stancl’s v3 integrations index is not a published Filament recipe):

1. **Identify the tenant before the shop panel runs.** Put stancl identification middleware (`InitializeTenancyByDomain` / `…ByDomainOrSubdomain` plus the matching prevent-central-domain middleware from the [quickstart](https://tenancyforlaravel.com/docs/v3/quickstart)) on shop Filament routes. Automatic mode switches the default DB connection, so tenant-schema Eloquent in Shop Filament then sees the tenant database. ([stancl introduction](https://tenancyforlaravel.com/docs/v3/introduction) — automatic mode switches database connections, cache, filesystem, queues)
2. **Platform Filament stays central.** Register it only on central domains (quickstart pattern: wrap central routes in `Route::domain($central)`). Do not initialize tenancy there. Tenant / Domain models live in the central DB.
3. **Two authenticatables, two panels.** Platform panel: central `User`. Shop panel: tenant `Staff` (Manager). Filament’s `HasTenants` examples assume `App\Models\User` belongs to many teams — that is the wrong user model for both panels here.
4. **Do not use Filament’s `->tenant()` URL prefix** as a substitute for stancl hostnames. Path identification is a stancl option (`InitializeTenancyByPath` + `/{tenant}`), and Embiken identifies by domain/subdomain, not path. ([Tenant identification](https://tenancyforlaravel.com/docs/v3/tenant-identification))
5. **`filament:install --scaffold` is dangerous** on this Inertia app (overwrites Vite/CSS). Panel install only. ([Filament 5 installation](https://filamentphp.com/docs/5.x/introduction/installation))

stancl v4 has extra SPA notes (Inertia `Inertia::location()` for cross-domain redirects; origin-header identification). Those are v4-only docs and v4 is not installable as stable. ([v4 Inertia integration](https://v4.tenancyforlaravel.com/integrations/inertia/); [v4 getting started](https://v4.tenancyforlaravel.com/getting-started/)) On 3.x, keep shop surfaces on the tenant hostname so identification middleware can run.

---

## 4. `lorisleiva/laravel-actions` — `^2.12`

Packagist **v2.12.0** (2026-08-25) requires:

- `php: ^8.2`
- `illuminate/contracts: ^11.0|^12.0|^13.0`

([Packagist `lorisleiva/laravel-actions`](https://packagist.org/packages/lorisleiva/laravel-actions))

Laravel 13 support was added in **v2.10.0** (“Add Laravel 13 support and drop Laravel 10”, PR #336). ([GitHub release v2.10.0](https://github.com/lorisleiva/laravel-actions/releases/tag/v2.10.0)) v2.9.1 is `illuminate/contracts: ^10.0|^11.0|^12.0` and will not resolve on Laravel 13.

v2.12.0 itself is PHPStan / route-parameter work, not a new Laravel bump. ([GitHub release v2.12.0](https://github.com/lorisleiva/laravel-actions/releases/tag/v2.12.0)) Recommend `^2.12` so we sit on the current 2.x line.

The package’s `require-dev` still lists `pestphp/pest: ^3.0|^4.0`. That does **not** constrain this app (Pest 5 is already in `require-dev` here); Composer does not install a dependency’s `require-dev`.

Official product: Action classes as controller / job / command / listener. ([laravelactions.com](https://laravelactions.com/)) No extra Laravel adapter package.

---

## 5. Stripe: PHP SDK yes, Cashier no (for rental Connect Express)

### Cashier is not the Connect Express path

Laravel 13 billing docs open with: “Laravel Cashier Stripe provides an expressive, fluent interface to **Stripe's subscription billing services**.” Installation is `composer require laravel/cashier`. There is no Connect Express, Account Link, destination charge, or `application_fee_amount` section. ([Laravel 13 billing](https://laravel.com/docs/13.x/billing))

Packagist description matches: “expressive, fluent interface to Stripe's **subscription billing** services.” Latest stable **v16.7.0** (2026-08-05) requires:

- `php: ^8.1`
- `illuminate/support` (and other illuminate packages): `^10.0|^11.0|^12.0|^13.0`
- `stripe/stripe-php: ^17.4|^18.0|^19.0|^20.0`

([Packagist `laravel/cashier`](https://packagist.org/packages/laravel/cashier); [GitHub release v16.7.0](https://github.com/laravel/cashier-stripe/releases/tag/v16.7.0))

So Cashier **can** install on Laravel 13, but:

1. It is the wrong product for “new Express account per shop, Account Link, capture PaymentIntent, 3% application fee.”
2. Stable v16.7.0 **cannot** take `stripe/stripe-php` 21. `16.x-dev` already lists `^17.4|^18.0|^19.0|^20.0|^21.0`, but that is not a stable tag. Installing Cashier *and* `stripe/stripe-php: ^21.3` would fail on v16.7.0.

**Recommendation: do not add `laravel/cashier` for rental Connect.** If platform shop subscriptions are added later, re-check Cashier’s `stripe/stripe-php` constraint before sharing one SDK major.

### `stripe/stripe-php` is the supported path

Latest stable **v21.3.0** requires `php: >=7.2.0` plus `ext-curl`, `ext-json`, `ext-mbstring`. No Illuminate constraint — it does not care about Laravel 13. ([GitHub `v21.3.0` composer.json](https://raw.githubusercontent.com/stripe/stripe-php/v21.3.0/composer.json); [GitHub release v21.3.0](https://github.com/stripe/stripe-php/releases/tag/v21.3.0) — pins API version `2026-08-26.dahlia`)

Official PHP examples for the APIs Embiken needs:

- Create Account + Account Link (`type` = `account_onboarding`, `return_url` / `refresh_url`): [Express connected accounts](https://docs.stripe.com/connect/express-accounts); [Create an account link (PHP)](https://docs.stripe.com/api/account_links/create?lang=php)
- Stripe is moving new platforms toward Accounts v2 / `controller` properties; the PHP create-account example uses `controller.stripe_dashboard.type = express`. The older `type=express` parameter is documented as deprecated on the create-account API. The SDK still exposes both. ([Create an account (PHP)](https://docs.stripe.com/api/accounts/create?lang=php); [Express accounts](https://docs.stripe.com/connect/express-accounts)) Product mapping (Account Link vs v2) belongs to the Stripe Connect research ticket, not this file. The **package** to call those APIs is still `stripe/stripe-php`.

Recommend `stripe/stripe-php: ^21.3`.

---

## 6. `laravel/reverb` — `^1.11`

Packagist **v1.11.1** (2026-08-06) requires:

- `php: ^8.2`
- `illuminate/console|contracts|http|support: ^10.47|^11.0|^12.0|^13.0`
- `pusher/pusher-php-server: ^7.2` (server-side Pusher protocol)

v1.7.0 still stops at Laravel 12 (`illuminate/*: ^10.47|^11.0|^12.0`). **v1.11.0+** is the Laravel 13 line.

Laravel 13 install: `php artisan install:broadcasting --reverb` or `composer require laravel/reverb` then `php artisan reverb:install`. ([Reverb](https://laravel.com/docs/13.x/reverb); [Broadcasting — Reverb](https://laravel.com/docs/13.x/broadcasting#reverb))

No conflict with Inertia Vue 3: Reverb is a WebSocket server; the SPA talks to it through Echo.

---

## 7. Front: Pinia, Echo, Reverb client (Inertia Vue 3)

There is **no separate “Reverb JS client” package**. Laravel 13: “install the `pusher-js` package since **Reverb utilizes the Pusher protocol**.” Echo `broadcaster: 'reverb'` requires **laravel-echo v1.16.0+**. Vue starter-kit path: `configureEcho` from `@laravel/echo-vue`. ([Broadcasting — Reverb client](https://laravel.com/docs/13.x/broadcasting#client-reverb); [Using React or Vue](https://laravel.com/docs/13.x/broadcasting#using-react-or-vue))

### npm latest (2026-08-31)

| Package | Latest | Peers / engines | Fits this repo? |
| --- | --- | --- | --- |
| `pinia` | **4.0.3** | `vue: ^3.5.11`; optional `typescript: >=5.6.0`; required `@vue/devtools-api: ^8.1.5` | Yes for Vue `^3.5.13`. TypeScript peer is optional; repo is `^5.2.2` |
| `pinia` 3.x last | 3.0.4 | `vue: ^3.5.11`; optional `typescript: >=4.5.0` | Also fits; 4.x is current |
| `laravel-echo` | **2.4.0** | optional peers `pusher-js`, `socket.io-client`; `engines.node: >=20` | Yes. Reverb minimum is 1.16.0; 2.4.0 is current |
| `@laravel/echo-vue` | **2.4.0** | `vue: ^3.0.0`; same optional pusher/socket peers | Yes with Vue 3 / Inertia Vue 3 |
| `pusher-js` | **8.6.0** | none | Yes. Install it; Echo’s pusher peer is optional but Reverb needs the protocol |

Sources: npm registry `pinia@4.0.3` / `3.0.4`, `laravel-echo@2.4.0`, `@laravel/echo-vue@2.4.0`, `pusher-js@8.6.0`; [Pinia 4.0.3 package.json](https://raw.githubusercontent.com/vuejs/pinia/v4.0.3/packages/pinia/package.json); [Echo v2.4.0 release](https://github.com/laravel/echo/releases/tag/v2.4.0).

Pinia has no Inertia peer. Official Pinia is a Vue store (`vue: ^3.5.11`). Inertia Vue 3 is a Vue 3 page adapter. They compose: install Pinia on the Vue app Inertia creates. (How to hydrate a Terminal day store is a separate ticket.)

Recommend:

```text
pinia@^4.0
laravel-echo@^2.4
pusher-js@^8.6
@laravel/echo-vue@^2.4   # optional; Laravel 13 Vue docs use this
```

Keep Echo and `@laravel/echo-vue` on the **same** 2.4.x line.

---

## 8. PHP 8.5-class support

This app requires `php: ^8.3`. Laravel 13 requires `php: ^8.3` and depends on `symfony/polyfill-php85` (and `polyfill-php84` / `php86`). ([`laravel/framework` 13.x composer.json](https://raw.githubusercontent.com/laravel/framework/13.x/composer.json)) Composer `^8.3` means `>=8.3.0 <9.0.0`, so **8.5 is in range**.

None of the recommended PHP packages declare an upper bound below 9.0:

| Package | PHP constraint |
| --- | --- |
| `stancl/tenancy` 3.10.1 | `^8.0` |
| `filament/support` 5.7.6 | `^8.2` |
| `livewire/livewire` 4.4.2 | `^8.1` |
| `lorisleiva/laravel-actions` 2.12.0 | `^8.2` |
| `stripe/stripe-php` 21.3.0 | `>=7.2.0` |
| `laravel/reverb` 1.11.1 | `^8.2` |
| `laravel/cashier` 16.7.0 (not recommended) | `^8.1` |

Packagist constraints allow PHP 8.5. That is not the same as a published PHP 8.5 CI matrix for every package; Laravel 13’s own `^8.3` + `polyfill-php85` is the first-party signal that 8.5 is in the intended window.

---

## Constraints that would block us

1. **`stancl/tenancy` v4** — docs require `dev-master`; no stable 4.x on Packagist; app is `minimum-stability: stable`.
2. **`stancl/tenancy` `< 3.10`** — no `illuminate ^13`.
3. **Filament `HasTenants` / `Panel::tenant()`** — wrong tenancy model (team switcher vs stancl hostname + database-per-tenant). Forbidden by Embiken law and contradicted by Filament’s own description of that feature.
4. **Filament 3** — Composer-legal on L13, but Livewire 3 / non-Tailwind-4 docs vs this starter kit’s Tailwind 4.1 + Vite 8 + Inertia Vue.
5. **Filament 5 + Livewire pinned to 4.1.0–4.1.4** — those Livewire tags lack Laravel 13.
6. **`filament:install --scaffold`** — official overwrite warning; would smash this Inertia/Vite app.
7. **`laravel/cashier` for Connect Express** — not documented as Connect; stable 16.7.0 also conflicts with `stripe/stripe-php` 21.
8. **`lorisleiva/laravel-actions` `< 2.10`** — no Laravel 13.
9. **`laravel/reverb` `< 1.11`** — no `illuminate ^13` (1.7.0 is L12-max).
10. **Pinia 4 without Vue ≥ 3.5.11** — not an issue here (`^3.5.13`).
11. **Echo Reverb broadcaster on Echo `< 1.16.0`** — Laravel 13 warning. Current npm latest is 2.4.0, so only a problem if we pinned 1.15.

Nothing in the recommended set requires a Composer stability change, a PHP bump, or replacing Inertia Vue 3.

---

## Sources (primary)

- Packagist p2 / package JSON: `stancl/tenancy`, `filament/filament`, `filament/support`, `lorisleiva/laravel-actions`, `laravel/reverb`, `stripe/stripe-php`, `laravel/cashier`, `livewire/livewire` (fetched 2026-08-31)
- GitHub `composer.json`: [stancl v3.10.1](https://raw.githubusercontent.com/archtechx/tenancy/v3.10.1/composer.json), [filament/support v5.7.6](https://raw.githubusercontent.com/filamentphp/filament/v5.7.6/packages/support/composer.json), [filament/support v4.12.6](https://raw.githubusercontent.com/filamentphp/filament/v4.12.6/packages/support/composer.json), [livewire v4.2.0](https://raw.githubusercontent.com/livewire/livewire/v4.2.0/composer.json), [stripe-php v21.3.0](https://raw.githubusercontent.com/stripe/stripe-php/v21.3.0/composer.json), [laravel/framework 13.x](https://raw.githubusercontent.com/laravel/framework/13.x/composer.json)
- GitHub releases: [tenancy v3.10.0](https://github.com/archtechx/tenancy/releases/tag/v3.10.0), [v3.10.1](https://github.com/archtechx/tenancy/releases/tag/v3.10.1), [laravel-actions v2.10.0](https://github.com/lorisleiva/laravel-actions/releases/tag/v2.10.0), [v2.12.0](https://github.com/lorisleiva/laravel-actions/releases/tag/v2.12.0), [livewire v4.2.0](https://github.com/livewire/livewire/releases/tag/v4.2.0), [reverb v1.11.0](https://github.com/laravel/reverb/releases/tag/v1.11.0), [cashier v16.7.0](https://github.com/laravel/cashier-stripe/releases/tag/v16.7.0), [echo v2.4.0](https://github.com/laravel/echo/releases/tag/v2.4.0), [stripe-php v21.3.0](https://github.com/stripe/stripe-php/releases/tag/v21.3.0)
- Docs: [stancl v3 introduction / identification / quickstart](https://tenancyforlaravel.com/docs/v3/introduction), [v4 getting started](https://v4.tenancyforlaravel.com/getting-started/), [Filament 5 / 4 / 3 installation](https://filamentphp.com/docs/5.x/introduction/installation), [Filament 5 tenancy](https://filamentphp.com/docs/5.x/users/tenancy), [Laravel 13 billing](https://laravel.com/docs/13.x/billing), [Reverb](https://laravel.com/docs/13.x/reverb), [Broadcasting](https://laravel.com/docs/13.x/broadcasting), [Stripe Express](https://docs.stripe.com/connect/express-accounts), [Account Links PHP](https://docs.stripe.com/api/account_links/create?lang=php), [Create Account PHP](https://docs.stripe.com/api/accounts/create?lang=php)
- npm registry: `pinia`, `laravel-echo`, `@laravel/echo-vue`, `pusher-js` (2026-08-31)
