<laravel-boost-guidelines>
=== .ai/embiken rules ===

# Embiken

SaaS for independent bike rental shops. Tenancy is stancl/tenancy: **database-per-tenant**, identified by **domain or subdomain**. A **Tenant** is one shop operator. A **Location** is a store in the tenant database, never a stancl tenant. v1 is one Location per tenant; do not add a location picker or path.

Mutating use cases are invokable Actions via lorisleiva/laravel-actions to the fullest: the Action is the API controller (`asController` / `jsonResponse`) and the Artisan command (`asCommand`) at minimum — do not add a thin `Http\Controller` or `Console\Command` for the same use case. `asCommand` takes the Action’s arguments (optional on the signature); missing values are prompted with `laravel/prompts` unless `--no-interaction` / `-n`. Tenant Actions take `--tenant` (id or domain) and initialize tenancy before `handle()`. Filament panels expose buttons that invoke those Actions where it makes sense (still never Connect or cash checkout, pickup, or return). **Availability** is the only occupancy seam (`App\Services\Availability`; each `bike_reservation` occupies the reservation’s `[starts_at, ends_at]` inclusive, under row locks; no occupancy ledger). Complex queries otherwise live in Eloquent scopes — Availability is the rare exception. Conflict also applies a turnaround buffer after `ends_at` (Location minimum, default 10 minutes; a BikeModel may set a longer padding; renter interval unchanged), `in_service`, Book’s `self_bookable`, and blocking service with an optional window. Return at exactly `ends_at` is allowed; the bike is not free for the next rental until after the buffer. Book must honor the buffer; Terminal may ignore it (still must not overlap another reservation’s `[starts_at, ends_at]`). Situation is not an allocate filter. `quoteOccupancy` does not write; a stale Out is not available until mutating Availability or the scheduled tick heals it. Data-model ancestor: `../embiken-brew`. Catalog is **BikeCategory → BikeModel → BikeModelVariant → Bike** (required chain). v1 has no add-on/helmet lines — bike catalog only. `product_id` on `bike_reservation` is the variant; there is no `Product` table; `product()` returns `BikeModelVariant`. Rates live on a **package × variant** pivot (`rate_cents`); a missing row means that variant is not in the offer (e-bike-only packages, etc.). Meter `per_line` is the fixed amount for that line for the whole interval. Meter `none` still uses the pivot for membership; `rate_cents` is 0. **RentalPackage** lives on the Reservation header, never on lines. Book always has a package (auto-select if one Book-visible, else the customer picks). Terminal may omit only for hand-entered quotes (any in-service product, staff sets `owed`). An attached package still requires pivot membership. A free package is valid. A package has one meter (`none`, `per_hour`, `per_line`, `per_calendar_day`) and a Book confirm threshold (`none`, deposit as cents or percent, or full). `per_hour` rounds `[starts_at, ends_at)` up to 15-minute steps. `per_calendar_day` counts distinct shop-timezone dates that intersect `[starts_at, ends_at)`. The package—not a global rule—defines what payment Book needs to leave Provisional. Staff may still set Confirmed without payment (package bar is Book and MyRental, not Terminal). No Allocation pool; Bike has required `bid` (shop-facing letter/number code), `in_service`, and `self_bookable` (Book counts both; Terminal may assign any in-service bike). Optional photo on Bike, BikeModelVariant, and BikeModel; display uses the first present in that order. Manufacturer serial, barcode, RFID, and QR wait. Location configures when `bike_id` is set: Terminal assign before pickup (default), Book may pin a specific bike, or persist `bike_id` only at pickup (earlier assign is display-only). Location also configures return situation: straight to `home`, or `back` then a put-away to `home` (default skip `back`). Terminal **may** set `prepping`/`staged` (skip allowed); pickup always `rented_out`. Swap is one Action: same variant first, then same model different size, then any in-service bike (requote or keep `owed`); a package still requires the new variant on the pivot. Core names: **Reservation** (not Booking), pivot `bike_reservation` (product required, `bike_id` nullable until assigned). Reservation `stage` is a cache: staff write Provisional, Confirmed, or Cancelled; never drive bike state from `stage` (Brew’s `SyncBikesSituationForBookingStage` is gone). Provisional is a short cart lock then Cancelled if not Confirmed or picked up: store `expires_at` and bump it on mutating Actions for that reservation (including a failed Connect capture); idle checkout does not bump; minutes stay a schema choice. **CancelAction** sets Cancelled and releases occupancy for lines that never went `Out` (Assigned / not picked up; prepping/staged go `home`). An `Out` bike is **not** auto-returned; staff is prompted to confirm those bikes are in the shop, then return as usual. Cancelled reservations do not occupy the class interval; an `Out` bike on a Cancelled reservation stays unavailable until returned. Do not heal that `Out` on allocate. Refund stays a separate Action. Confirmed with no `Out`/`In` past `ends_at` caches **No Show**, releases unused lines, and sends prepping/staged bikes `home`. The No Show reservation still occupies through `ends_at` plus the turnaround buffer — Book cannot start the next rental at the due time. A late original customer does not keep the slot. Renters pay shops via **Stripe Connect Express** on Book (new connected account per shop; do not attach an existing full Stripe account). Each Connect charge **captures** the package confirm amount immediately (deposit or full `owed`, not a card hold) with a **3%** application fee to the platform. Remainder after a deposit is a later charge on MyRental (Connect capture of outstanding) or Terminal (`cash` / `other`). Book does not charge again after Confirmed. Terminal staff (Counter and Manager) may refund via a Refund Action (Connect refund through Stripe; `cash`/`other` ledger-only) and may park a bike (`in_service` false, blocking service, put-away). Filament refunds stay Manager-only. Book and MyRental do not self-refund; occupancy unchanged. Book pay is disabled until the Express account can charge, except a `$0` / `none` confirm package can still Confirmed on Book. Staff may still set Confirmed without payment. Reservation stores **owed** and **paid** (caches); **transactions** are the money history. Shop Filament is configuration plus a Manager Reservation editor (stage, owed, interval, and lines via Actions) and Manager refunds; it still never takes Connect or cash checkout. `owed` is the package quote in Location currency (default `usd`); no tax in v1. Hours live on the single Location (one or more open intervals per weekday; an interval may close after midnight; optional all-day closed dates). Book (and MyRental extend) require `starts_at` and `ends_at` each to fall during an open interval, not on a closed date — a closed night between them is allowed; Terminal and Filament may override. Hours are Book and MyRental Action validation, not `quoteOccupancy` or `allocate`. Hours never allocate and never fold `stage`: pickup and return may be a subset of lines; any `Out` caches `Out`; all lines `In` before `ends_at` caches Returned; all `In` and `ends_at` passed is Completed immediately (tick at `ends_at` if they returned early). Early return does not lower `owed`. Nothing changes at shop close. Damage is Filament notes only: it does not change `owed` or occupy a bike. Extend is reservation-level (`ends_at`) through Availability, not Provisional; Terminal may always extend and choose requote or leave `owed`; a late return prompts the same requote-or-keep choice (requote uses the interval through return time). MyRental may request an extend (always requote; confirm bar on the new `owed` before commit); Book does not. Cancel is Terminal and Filament only — not Book or MyRental. Terminal may set `in_service` false or open blocking service at return; damage prose stays Filament.

New tenants get a subdomain immediately; custom domains are an extra Domain row later (Platform Filament may add, edit, or remove Domain rows after create). Tenant create provisions the DB, one Location (timezone and currency from the platform form, empty hours, 10-minute buffer), one invitible Manager (email + set-password invite, no password on the tenant form), one **Platform Manager** Staff row (locked label Platform; no password, no invite, no Staff login), and starts Express Account Link. A User may view Express status and retry Account Link from Platform Filament. **Suspend** padlocks the shop host: Book, Terminal, and `/manage` refuse Staff sign-in; User may unsuspend. **Tenant delete** is only while suspended: removes the central Tenant and Domain rows, must not drop the shop database, no restore in shop-operable v1. Shop Filament invites Counter and additional Managers the same way as the invitible Manager (never the Platform Manager). Catalog, hours, and Book-visible packages are created in Shop Filament from empty states — do not seed a demo fleet or shop hours. Book with no Book-visible package or with empty hours is an empty “not configured” state; Suspend is a distinct Book-off state; Express unable to charge is a third Book-off (only `$0`/`none` confirm packages, or checkout unavailable). The central apex is Platform Filament only (Tenants, Domains, Users). Ops `/t/{tenant}/…` on the apex stays out of shop-operable v1 and Wayfinder must not emit those URLs for the SPA. A User **impersonates** by redirect to the tenant host as that tenant’s Platform Manager (`/manage` and `/terminal`, including while suspended); Exit returns to Platform Filament. Do not impersonate a real Manager. Do not use Filament `HasTenants`.

## Surfaces

One Vue SPA except for the Filament parts. Wayfinder for every frontend route. Inertia GET for first paint (`htmlResponse`); mutating Actions are JSON (`jsonResponse`, `useHttp`). That JSON is app IO (SPA, signed MyRental, ops; Device Sanctum tokens later), not a public partner `/api/v1`. Use API Resources. `GET /` redirects to `/book`.

| Surface | Path | Who | Role |
| --- | --- | --- | --- |
| Book | `/book` | public / customer | Browse, provisional, pay the package confirm threshold to confirm |
| MyRental | `/myrental` | customer | That reservation until 3 days after `ends_at`; session and signed link; may request extend; remainder Connect |
| Terminal | `/terminal` | staff (password session) | Desk and floor POS: large buttons, speed over web convention; walk-in, assign/swap, pickup/return, extend, cash/other. Counter PCs and tablets. Device pairing later |
| CFD | `/cfd` | device | Customer display, paired Terminal focus. Customer may edit name/email/phone on the CFD (live both ways with Terminal). Waiver in v1 is a timestamped checkbox; stored signature later. No staff controls |
| Screen | `/screen` | device | Shop board. Hydrate Pinia for the day, Echo patches the store. No controls |
| Shop Filament | `/manage` | managers | Catalog, fleet, hours, pricing, staff, damage notes, writable Reservation (stage/owed). Never Connect checkout. Devices resource later |
| Platform Filament | central host | User | Tenants, Domains, Users, Express status/retry, Suspend/unsuspend, Tenant delete without dropping the DB. Impersonate Platform Manager on the tenant host. Shop subscriptions later. Not shop rental checkout |

Pages live under `resources/js/pages/{Book,MyRental,Terminal,Cfd,Screen}/`. Terminal is a restaurant-style P.O.S.: large hit targets for one-finger tapping on a fixed counter screen or a tablet held in the other hand; few screens; trained speed — not a marketing or admin web layout. Book, MyRental, CFD, Screen, and Filament do not copy that chrome.

Auth: platform **Users** (central DB, one admin class). The first User is Artisan or a local seeder (password on the command); Platform Filament invites more with a set-password invite; disable a User (cannot sign in); do not delete Users; no public register. **Staff** and **Customer** are separate tenant authenticatable models with separate guards. Staff roles: **Manager** (`/manage` + `/terminal`) vs **Counter** (`/terminal` only, including refund and parking a bike). Staff sign in with a password after a set-password invite, except the **Platform Manager** (no password, no Staff login; User impersonation only). Shop-operable **Terminal is staff session only** — do not require a bound Device or Sanctum device token. Device pairing (Device row bound to Location + surface, one-time Filament code, Sanctum token on Device not Staff, CFD `paired_terminal_device_id`, Shop Filament Devices resource) is a later slice; then Terminal becomes staff plus device without changing occupancy or the location channel. When CFD and Screen exist they are device-only. Devices are not users. Book is guest until reserve; then a Customer exists (name, email, and phone required on Book and Terminal). Book reuses a Customer on email match (updates name/phone). Terminal matches on email and phone, and may force-create a new row anyway (no unique constraint). MyRental: signed URL (mint once; show on Book Confirmed whenever the Customer session resumes; staff may reveal; do not email it in shop-operable v1) plus a Customer session on that browser after Book — stay signed in; `/book` resumes checkout or Confirmed while the reservation is in `ends_at` + 3 days; access until 3 days after `ends_at`. No password gate. No customer self-cancel. Emails: confirm (with link), reminder before start, Connect receipt, return thanks; no No Show mail. There is no kiosk surface and no Pad surface; pickup/return is Terminal. Shop-floor Vue uses a shared Pinia store on Terminal (and Screen when that surface exists): first paint hydrates every bike plus reservations that intersect today (shop timezone) and any reservation whose bikes are not `home`; Echo patches that store; components read the store. Authorize the location channel for **staff**. Terminal and screen device tokens join that channel in the later Device slice. Location-channel DTOs may include customer display name and owed/paid; Screen must not render PII or money. Book and MyRental do not use that store. CFD (later) hydrates a ticket store from `tenant.{tenantId}.cfd.{cfdDeviceId}`; the paired Terminal then also subscribes to that CFD channel. Location board channel is `tenant.{tenantId}.location.{locationId}`.

## How we write decisions

Never hand-edit `AGENTS.md`, `CLAUDE.md`, or `.ai/rules/boost/**`. Always-on law goes in `.ai/guidelines/embiken.md`, then `php artisan boost:update --no-discover --no-interaction`. Path-scoped law via Boost `record-rule`. Do not duplicate essays. `CONTEXT.md` is the glossary (what a word means, `_Avoid_`); this file is law (must / must-not); `docs/adr/` is why. Do not paste the glossary here or put law in `CONTEXT.md`.

## Agent skills

### Issue tracker

GitHub Issues on `paulruderman/embiken-core` via `gh`. See `docs/agents/issue-tracker.md`.

### Domain docs

Single-context: `CONTEXT.md` (glossary) and `docs/adr/` (why) at the repo root, created lazily. This file is law. See `docs/agents/domain.md`.

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
