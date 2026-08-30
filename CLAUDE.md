<laravel-boost-guidelines>
=== .ai/embiken rules ===

# Embiken

SaaS for independent bike rental shops. Tenancy is stancl/tenancy: **database-per-tenant**, identified by **domain or subdomain**. A **Tenant** is one shop operator. A **Location** is a store in the tenant database, never a stancl tenant. v1 is one Location per tenant; do not add a location picker or path.

Mutating use cases are invokable Actions. **Availability** is the only occupancy seam (each `bike_reservation` occupies the reservation’s half-open `[starts_at, ends_at)`, under row locks; no occupancy ledger). Conflict also applies the BikeModel turnaround buffer (renter interval unchanged), `in_service`, Book’s `self_bookable`, and blocking service with an optional window. Situation is not an allocate filter. `quoteOccupancy` does not write; a stale Out is not available until mutating Availability or the scheduled tick heals it. Data-model ancestor: `../embiken-brew`. Catalog is **BikeCategory → BikeModel → BikeModelVariant → Bike** (required chain). `product_id` on `bike_reservation` is the variant; there is no `Product` table; `product()` returns `BikeModelVariant`. Rates live on a **package × variant** pivot (`rate_cents`); a missing row means that variant is not in the offer (e-bike-only packages, etc.). Meter `per_line` is the fixed amount for that line for the whole interval. Meter `none` still uses the pivot for membership; `rate_cents` is 0. **RentalPackage** lives on the Reservation header, never on lines. Book always has a package (auto-select if one Book-visible, else the customer picks). Terminal may omit only for hand-entered quotes (any in-service product, staff sets `owed`). An attached package still requires pivot membership. A free package is valid. A package has one meter (`none`, `per_hour`, `per_line`, `per_calendar_day`) and a Book confirm threshold (`none`, deposit as cents or percent, or full). `per_hour` rounds `[starts_at, ends_at)` up to 15-minute steps. `per_calendar_day` counts distinct shop-timezone dates that intersect `[starts_at, ends_at)`. The package—not a global rule—defines what payment Book needs to leave Provisional. Staff may still set Confirmed without payment (package bar is Book and MyRental, not Terminal). No Allocation pool; Bike has `in_service` and `self_bookable` (Book counts both; Terminal may assign any in-service bike). Core names: **Reservation** (not Booking), pivot `bike_reservation` (product required, `bike_id` nullable until assigned). Reservation `stage` is a cache: staff write Provisional, Confirmed, or Cancelled; never drive bike state from `stage` (Brew’s `SyncBikesSituationForBookingStage` is gone). Provisional is a short cart lock (about 10–15 minutes) then Cancelled if not Confirmed or picked up. Renters pay shops via **Stripe Connect Express** on Book (new connected account per shop; do not attach an existing full Stripe account). Each Connect charge takes a **3%** application fee to the platform; cash and `other` have no Stripe fee. Terminal records `cash` or `other` (optional note) for now; Stripe Terminal readers wait. Book auto-flips Provisional → Confirmed when the package’s confirm threshold is met (`paid` vs `owed`); not always pay-in-full. Reservation stores **owed** and **paid** (caches); **transactions** are the money history. Shop Filament is configuration plus a Manager Reservation editor (stage, owed, interval, and lines via Actions); it still never takes Connect or cash checkout. Hours live on the single Location (weekly open/close, optional closed dates) and are quote-only. Damage is Filament notes only: it does not change `owed` or occupy a bike. Extend is reservation-level (`ends_at`) through Availability, not Provisional; Terminal may always extend and choose requote or leave `owed`; MyRental may request an extend (always requote; confirm bar on the new `owed` before commit); Book does not. Terminal may set `in_service` false or open blocking service at return; damage prose stays Filament.

New tenants get a subdomain immediately; a custom domain is an extra Domain row later. The central apex is Platform Filament only. Ops may open tenant surfaces at `/t/{tenant}/…` on the apex (platform auth, or local); those URLs are not customer links and Wayfinder must not emit them for the SPA.

## Surfaces

One Vue SPA except for the Filament parts. Wayfinder for every frontend route. `GET /` redirects to `/book`.

| Surface | Path | Who | Role |
| --- | --- | --- | --- |
| Book | `/book` | public / customer | Browse, provisional, pay the package confirm threshold to confirm |
| MyRental | `/myrental` | customer | That reservation before and during the rental; may request extend |
| Terminal | `/terminal` | staff + bound device | Desk and floor: walk-in, assign/swap, pickup/return, extend, cash/other. Counter PCs and tablets |
| CFD | `/cfd` | device | Customer display, paired to a Terminal device. Own Echo channel. No staff controls |
| Screen | `/screen` | device | Shop board. Hydrate Pinia for the day, Echo patches the store. No controls |
| Shop Filament | `/manage` | managers | Catalog, fleet, hours, devices, pricing, staff, damage notes, writable Reservation (stage/owed). Never Connect checkout |
| Platform Filament | central host | platform | Tenants, domains, shop subscriptions later. Not shop rental checkout |

Pages live under `resources/js/pages/{Book,MyRental,Terminal,Cfd,Screen}/`.

Auth: platform users (central DB, `User`). **Staff** and **Customer** are separate tenant authenticatable models with separate guards. Staff roles: **Manager** (`/manage` + `/terminal`) vs **Counter** (`/terminal` only). Devices are not users: a Device bound to Location + surface, Sanctum token, paired with a one-time code in shop Filament. A CFD device also stores `paired_terminal_device_id`. Device-only in daily operation: **cfd**, **screen**. Staff plus device: **terminal**. Book is guest until reserve; then a Customer exists; MyRental is a signed or magic link. There is no kiosk surface and no Pad surface; pickup/return is Terminal. Shop-floor Vue uses a shared Pinia store on Terminal and Screen: first paint hydrates every bike plus reservations that intersect today (shop timezone) and any reservation whose bikes are not `home`; Echo patches that store; components read the store. Location-channel DTOs may include customer display name and owed/paid; Screen must not render PII or money. Book and MyRental do not use that store. CFD hydrates a ticket store from `tenant.{tenantId}.cfd.{cfdDeviceId}`. Location board channel is `tenant.{tenantId}.location.{locationId}`.

## How we write decisions

Never hand-edit `AGENTS.md`, `CLAUDE.md`, or `.ai/rules/boost/**`. Always-on law goes in `.ai/guidelines/embiken.md`, then `php artisan boost:update --no-discover --no-interaction`. Path-scoped law via Boost `record-rule`. Do not duplicate essays.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
