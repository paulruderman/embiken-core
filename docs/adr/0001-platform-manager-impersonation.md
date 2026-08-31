# Platform Manager impersonation on the tenant host

A User must be able to operate a shop (including while **Suspended**) without a second password and without mounting tenant surfaces on the apex. Ops `/t/{tenant}/…` is out of shop-operable v1; Filament `HasTenants` is out (User is not a tenant member). Impersonating a real Manager would pin the backdoor on a person the shop can disable.

We create one **Platform Manager** Staff row per Tenant (not a person, no Staff login) and impersonate only that row: redirect to the tenant host as Staff, Exit back to the apex. Shop Filament shows it locked as “Platform”. That keeps User ≠ Staff except this crossing, gives Actions a Staff actor, and leaves `/t/` and `HasTenants` rejected.

## Considered Options

- Two passwords (User on apex, Manager on shop host) — rejected: the operator would always keep a second login
- Apex `/t/{tenant}/…` with the User session — rejected: out of this spec family; clones shop routes onto the platform domain
- Filament `HasTenants` on User — rejected: User is not a tenant member; Staff is
- Impersonate the invitible first Manager — rejected: the shop could disable that person and shut the backdoor
