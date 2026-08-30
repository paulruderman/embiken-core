---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Inertia surfaces by page tree
Pages live under resources/js/pages/{Book,MyRental,Terminal,Cfd,Screen}/. Do not add a Pad page tree. Do not put shop configuration screens in Inertia; those belong in Filament at /manage. Surface first paint is Inertia; writes go through Action JSON.

## Guest Book, MyRental session and signed URL
Book does not require a Customer session to browse or take a provisional. After reserve, start a Customer session on that browser and keep them signed in. Also email a signed MyRental URL, show it once on Book, and let Terminal re-send. URL and MyRental access last until 3 days after ends_at. Do not build a Customer password gate. Name, email, and phone are required on Book and Terminal. Show a specific-bike picker on Book only when Location policy is book_may_pin.

## No ops /t/ URLs in the SPA
Surface links use unprefixed tenant-host paths via Wayfinder. Do not send users to /t/{tenant}/….

## Outstanding balance reminders
When owed != paid, remind staff to collect or refund at pickup, return, and whenever a Reservation or Customer is shown on Terminal or MyRental. MyRental may take a Connect remainder payment. Do not hide the record. Do not put a customer self-refund control on Book or MyRental. Terminal cancel UI prompts to confirm Out bikes are in the shop; do not auto-return them. Book Confirmed follows the package confirm threshold, not a global pay-in-full or deposit-only rule.

## Book pays the package threshold, not always full
Book always uses a package. Self-serve Confirmed when paid meets that package's confirm threshold (none, deposit, or full), capturing that amount immediately on Connect. Do not assume Book is pay-in-full. Do not assume Book is deposit-only. If Express cannot charge, hide pay unless the threshold is $0/none. Terminal hand quotes may omit a package. Book (and MyRental extend) pickers must place starts_at and ends_at each during Location open hours, not on closed dates; overnight spanning a closed night is allowed. Terminal and Filament interval UI may override.

## MyRental can request extend; no Terminal damage charge
MyRental may request to extend the reservation window. Book does not. Terminal has no damage-charge UI; damage write-up is Filament.

## Extend and return UI vs damage notes
MyRental extend always requotes and waits on the package confirm bar. Terminal extend UI includes keep-owed vs requote. Return UI may mark the bike not in service or open blocking service. No damage fee. Terminal may re-send the MyRental signed URL.

## CFD ticket vs Screen day board
Cfd pages show only the paired Terminal's open ticket from Pinia (fed by the CFD channel). Screen and Terminal use the location-channel day store. CFD has no staff controls. Book and MyRental do not subscribe to shop-floor channels unless a later decision says so.

## Screen does not render PII or money
Screen pages must not render customer display name, owed, or paid even though Pinia may contain them. Terminal may. CFD shows the paired ticket only.

## Terminal page tree, no Pad
Pages live under Terminal/, not Station/ or Pad/. Counter PCs and floor tablets use the same Terminal app.
