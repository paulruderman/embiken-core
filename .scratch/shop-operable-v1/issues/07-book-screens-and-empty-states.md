# Book screens and empty states

Status: resolved
Type: grilling

## Question

What is the **written screen list** for Book in this family (no prototype), from empty shop to Confirmed?

Settled: not-configured when hours are empty or no Book-visible package; **Tenant Suspend** padlocks Book (Staff also cannot use Terminal or `/manage`; copy for the Book-off state is this ticket); guest until reserve; Customer name/email/phone required; reuse Customer on email; package always (auto if one Book-visible); confirm-threshold Connect capture; waiver checkbox; mint signed MyRental URL and show it **once** on confirm (visit 404s); no mail; no MyRental pages; no customer cancel/refund.

Lock the steps and empty/error states:

- Order: interval, package, lines/variants, contact, waiver, pay, confirm (reorder if needed)
- Book while the Tenant is Suspended (distinct from not-configured)
- Sold out / allocate fail after a quote
- Express cannot charge (hide pay unless `$0`/`none` threshold)
- Connect fails mid-pay
- Provisional expires mid-cart
- What the Confirmed page shows (including the once-only signed URL)

Do not spec Terminal. Do not add email. Record any product amendment with `record-rule`.

## Answer

**Empty states (before any cart), distinct copy:**

- **Not-configured** (no Book-visible package or Location hours empty): “This shop isn’t taking online bookings yet.”
- **Suspended:** “Online booking is unavailable.” Do not say Suspend, hours, or packages.
- **Express cannot charge** (hours and packages exist; `charges_enabled` + `transfers` not active): hide pay; offer only `$0`/`none` confirm packages; if none, “Online checkout isn’t available yet.” Do not let them build a cart they cannot confirm.

**Happy path — four screens** (not a seven-step wizard). Guest until allocate.

1. **Interval** — `starts_at` / `ends_at`. Hours errors inline (Book Action), not Availability.
2. **Offer** — package picker only if more than one Book-visible package (one → skip); variant quantities as unit lines; `quoteOccupancy` remaining; `remaining` 0 cannot be chosen; `bid` picker only if Location is `book_may_pin`.
3. **Checkout** — name, email, phone, waiver, then **allocate** (Provisional + Customer session) **then** pay if the threshold is not `$0`/`none`.
4. **Confirmed** — interval, package, lines, owed/paid, waiver accepted, signed MyRental URL (token minted once; **shown whenever** they view Confirmed in this session). `GET /myrental` still 404s. No cancel, no refund, no mail.

**Lock / TTL.** Browse is `quoteOccupancy` (no lock). Occupancy locks at checkout allocate. Store **`expires_at`** on the Reservation; bump it on every mutating Action for that row, including a **failed Connect capture**. Bump on allocate (first and amendments), contact/waiver writes, pay attempts (success or fail). Do not bump on reads, idle checkout, or offer browse before a reservation exists. Minutes stay on [Shop-operable schema product fields](11-shop-operable-schema-fields.md). Tick CancelAction when `expires_at` has passed and stage is still Provisional with no Out.

**Resume (amend “show once” / email-the-link).** Same browser, Customer session, reservation in `ends_at` + 3 days: `/book` **resumes** — Provisional → checkout; Confirmed → receipt with URL still shown. **Book another** starts a new interval without CancelAction on the current one. No session → interval (empty cart). Terminal may still reveal the URL.

**Back after lock:** amend the same Provisional (`allocate` / `release`); bump `expires_at`; keep contact. Do not CancelAction + create.

**Allocate fail after quote:** no half-Provisional; back to offer; refresh remaining; “Those bikes just became unavailable.” Keep interval (and package if still valid). Amendment fail: stay on this reservation, same flash. `not_on_package` is not customer copy.

**Pay fail:** stay Provisional; stay on checkout; “Payment didn’t go through. Try again.” Do not CancelAction. Bump `expires_at`.

**Expiry:** occupancy gone; “Your hold expired. Pick your bikes again.” Back to offer with the same interval. No resurrection.

**Padlock mid-cart:** next Book request shows that empty state. Do not pay or amend. Leave Provisional for the tick. No CancelAction from Book.

Rationale: [ADR-0003](../../../docs/adr/0003-book-resume-and-provisional-ttl.md).
