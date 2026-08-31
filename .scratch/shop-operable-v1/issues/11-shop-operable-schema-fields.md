# Shop-operable schema product fields

Status: open
Type: grilling
Blocked by: 09, 10

## Question

Which **product-visible fields** exist on the shop-operable tables, beyond what law already names (`starts_at`, `ends_at`, `stage`, `owed`, `paid`, `rental_package_id`, `customer_id`, `waiver_accepted_at`, `product_id`, `bike_id`, pivot Assigned/Out/In and check timestamps, `bid`, `in_service`, `self_bookable`, package meter and confirm threshold, package×variant `rate_cents`, Location hours/currency/buffer/assignment/return situation)?

Still product, not PK/timestamp trivia:

- Rider fields on `bike_reservation` (Brew had height/name) — yes or no in this family
- Reservation `source` (Book vs Terminal) — yes or no
- Provisional expiry: exact TTL minutes (10 vs 15 vs something else). **Settled by [Book screens](07-book-screens-and-empty-states.md):** stored `expires_at`, bumped on mutating Actions including failed pay; not derived from `created_at`
- Damage notes: columns on Bike and/or Reservation vs a notes table
- Catalog marketing: description, sort order, size label on variant
- `transactions` status machine (pending vs captured) now that Connect onboarding is decided
- Signed MyRental token storage (column vs table) — mint once, display on Confirmed whenever the session resumes ([Book screens](07-book-screens-and-empty-states.md)); shape is not

Blocked by [Action inventory for Book, Terminal, and ticks](09-action-inventory.md) and [Connect onboarding in Embiken](10-connect-onboarding-in-embiken.md). Implementation-only columns (UUIDs, soft deletes) stay out of this ticket unless they change a product rule.
