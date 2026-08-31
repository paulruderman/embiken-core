# Platform User admin envelope

Status: resolved
Type: grilling

## Question

What can a **platform User** do in Platform Filament in this family, beyond the already-settled Tenant create (DB, Location, first Manager invite, subdomain Domain, start Express Account Link)?

Q7 chose to **include platform User admin** in this map. Still out: shop subscriptions, ops `/t/{tenant}/…`, rental checkout.

Decide:

- Create / invite / disable platform Users — who is the first User (seed?), invite vs password on form
- Whether platform Users have roles or are a single admin class
- Whether they can edit Domains after create, retry Express Account Link, view Express status
- Whether they can impersonate or open shop Filament (ops `/t/` is out — is there any other path?)
- Whether Tenant delete remains “no drop DB” as the only delete policy, and if delete is in the UI at all

Do not re-open Tenant vs Location. Record amendments with `record-rule` if the answer changes guidelines.

## Answer

**User:** one admin class. First User via Artisan / local seeder (password on the command). More Users: Platform Filament set-password invite. Disable only (cannot sign in). No User delete, no public register, no roles.

**After Tenant create:** User may add/edit/remove **Domain** rows, view Express status, and retry Account Link. Shop-side who else may retry stays on [Connect onboarding in Embiken](10-connect-onboarding-in-embiken.md).

**Suspend:** Tenant row stays. Shop host padlocked: Book, Terminal, and `/manage` refuse **Staff** sign-in. User may unsuspend.

**Tenant delete:** only while suspended. Removes the central Tenant and Domain rows. Does **not** drop the shop DB. No restore in this spec family.

**Platform Manager:** a Manager Staff row created at Tenant create beside the invitible first Manager. Not a person. No password, no invite, no Staff login. Visible in Shop Filament Staff as locked “Platform”; shop cannot edit/disable/delete it.

**Impersonation:** User redirects to the **tenant host** as that Platform Manager (Staff session). Apex User session pauses; Exit returns to Platform Filament. `/manage` and `/terminal` (full Manager), including while suspended. Not `/t/…`, not `HasTenants`, not impersonating a real Manager. Filament still never pickup/return/put-away/checkout; Terminal as Platform Manager may.

Glossary: `CONTEXT.md`. Rationale: [ADR-0001](../../../docs/adr/0001-platform-manager-impersonation.md).
