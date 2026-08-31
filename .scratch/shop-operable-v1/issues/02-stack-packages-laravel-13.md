# Stack packages for Laravel 13

Status: resolved
Type: research

## Question

Which **currently installable** versions of these packages work together on this repo’s PHP and Laravel (see `composer.json` / `package.json`), and what constraints would block us?

Packages the recorded law requires and `composer.json` / `package.json` do not yet list:

- `stancl/tenancy` (database-per-tenant, domain/subdomain)
- Filament (v3 vs v4 vs v5 — Brew used Filament 5; do not assume)
- `lorisleiva/laravel-actions`
- Stripe PHP SDK and/or Laravel Cashier **only if** Cashier is the supported path for Connect Express (rental Connect is not platform Cashier subscriptions)
- `laravel/reverb`
- Front: Pinia, Laravel Echo, `pusher-js` or Reverb’s Echo client — versions that work with Inertia Vue 3 already in this repo

Need from Packagist/npm + official docs + GitHub compatibility statements: version ranges, Laravel 13 / PHP 8.5 support, known tenancy×Filament traps (HasTenants is forbidden here).

Write findings to `.scratch/shop-operable-v1/research/stack-packages.md` with citations. Recommend a version set; do not run `composer require`.

## Answer

Recommended install set on this Laravel 13 / PHP `^8.3` / Inertia Vue 3 repo: `stancl/tenancy` `^3.10` (not v4 — `dev-master` only), Filament `^5.0` (current; v4 still Composer-legal; skip v3), `lorisleiva/laravel-actions` `^2.12`, `stripe/stripe-php` `^21.3` and **no Cashier** (Cashier is subscriptions and v16.7.0 cannot take stripe-php 21), `laravel/reverb` `^1.11`, front `pinia` `^4.0` + `laravel-echo` `^2.4` + `pusher-js` `^8.6` (Reverb speaks Pusher; there is no separate Reverb JS client). Do not use Filament `HasTenants` — that is a team-switcher, not stancl database-per-tenant.

Full citations and blockers: [research/stack-packages.md](../research/stack-packages.md).
