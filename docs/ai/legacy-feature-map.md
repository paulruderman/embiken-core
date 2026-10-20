# Legacy feature map

Use the sister projects to discover prior user-visible behavior, edge cases, and useful interaction ideas. They are not the source of truth for the new app. Current law remains `.ai/guidelines/embiken.md` and the applicable `.ai/rules/` files.

## Repositories

| Repository | Likely value | Caution |
| --- | --- | --- |
| `../embiken-brew` | Latest legacy booking/desk workflows, maintenance, display flows, and roadmap notes | Architecture and domain model differ from core; terms such as Booking and Pad are legacy |
| `../embiken-26` | Earlier self-booking, front desk, catalog, and CFD interfaces | Contains legacy resource paths and partially obsolete screens |
| `../embiken-2` | Older requirements and feature notes, including `docs/Features.md` | Treat dated notes as questions to validate, not commitments |
| `../embiken-admin` | Older management and availability UI | Older tenancy and admin assumptions may not apply |

## Feature areas to survey when a slice needs them

| New surface/capability | Legacy starting points | What to extract |
| --- | --- | --- |
| Book | `embiken-26/resources/js/pages/Embiken/SelfBook/`; search other repos for booking/self-book flows | Browse/filter, party composition, customer details, quote, confirmation, unavailable states |
| Terminal / reservation handling | `embiken-brew/resources/js/pages/Embiken/BudPad/`; `embiken-26/resources/js/pages/Embiken/FrontDesk/` | Staff workflow, line-level assignment/pickup/return, speed affordances, failure recovery |
| Fleet and catalog | `embiken-26/resources/js/pages/Embiken/BackOffice/Resources.LEGACY/`; search models/resources in all repos | Catalog hierarchy, bike identity, service/parking, photos and configuration |
| Customer display / shop board | `embiken-brew/resources/js/pages/Viewscreens/` and `Viewfeeds/`; `embiken-26/resources/js/pages/Embiken/Cfd/` and `Viewscreens/` | What is shown, live updates, privacy boundaries, pairing assumptions |
| MyRental / payments | Search each repo for customer booking views, checkout, Stripe, and confirmation mail | Customer access, payment stages, receipts, extension/cancellation behavior |
| Tenant/platform operations | `embiken-admin` and `embiken-brew` Filament resources; inspect relevant tenancy notes | Provisioning, staff invitations, domains, suspension and administrative workflows |

These are starting points, not a complete audit. Add exact file and line references to the slice brief after inspecting the implementation and its tests.

## First survey to run

Start with **Terminal reservation handling**, because it exercises the core Reservation-as-a-party model and exposes operational edge cases that affect Availability, Book, and the shop-floor views. The initial read-through found that the legacy apps model stage transitions at the booking level; this does not establish that they support current line-level pickup and return. Keep those concepts distinct during the survey.

### Initial evidence: reservation handling

| Source | Observation | New-app implication |
| --- | --- | --- |
| `../embiken-brew/resources/js/pages/Embiken/BudPad/BookingShow.vue` | The detail page presents a booking summary and bike count, provides a booking stage selector, and removes an individual bike by `bike_id`. | Useful staff interaction reference, but it does not show per-bike pickup/return controls. Current Reservation views must render every line and line-level actions remain the new design. |
| `../embiken-brew/tests/Feature/Tenant/BookingStageActionsTest.php` | Tests cover provisional, confirmed, cancelled, checked-out, and checked-in booking actions. Explicit tests assert that checked-out/checked-in stage changes do not change bike situation. | Stage and bike situation were separate in this legacy test path. Current rules define Reservation stage as a cache and bike state through line-level actions; do not port these transitions as-is. |
| `../embiken-26/resources/js/pages/Embiken/FrontDesk/Bookings/Edit.vue` | The front desk edit form changes booking interval and stage and submits the booking as a whole. | Historical editing workflow reference only; compare with current Manager editor and Actions before using any interaction. |

This is an initial evidence note, not a completed feature specification. To complete the survey, trace the old bike situation changes and counter controls in both versions, inspect the related tests, and then draft one bounded slice brief. Capture:

- The old staff steps and visible state transitions.
- Whether each step applied to one bike or the whole reservation.
- Validation, conflict, and recovery behavior from tests and actions.
- Which old affordances are still useful on a fast counter interface.
- Every mismatch with current rules, especially line-level actions, occupancy, and cached reservation stage.

Then write a slice brief and compare it with the current code before implementation. The survey itself does not authorize copying an old implementation or broadening the slice.
