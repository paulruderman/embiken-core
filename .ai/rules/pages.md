---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Inertia surfaces by page tree
Pages live under resources/js/pages/{Book,MyRental,Terminal,Cfd,Screen}/. Do not add a Pad page tree. Do not put shop configuration screens in Inertia; those belong in Filament at /manage.

## Guest Book, MyRental by link
Book does not require a Customer session to browse, take a provisional reservation, or reserve. After reserve, MyRental is reached by signed or magic link. Do not build a Customer password gate on Book in v1.

## No ops /t/ URLs in the SPA
Surface links use unprefixed tenant-host paths via Wayfinder. Do not send users to /t/{tenant}/….

## Outstanding balance reminders
When owed != paid, remind staff to collect or refund at pickup, return, and whenever a Reservation or Customer is shown on Terminal or MyRental. Do not hide the record. Book Confirmed follows the package confirm threshold, not a global pay-in-full or deposit-only rule.

## Book pays the package threshold, not always full
Book always uses a package. Self-serve Confirmed when paid meets that package's confirm threshold (none, deposit, or full). Do not assume Book is pay-in-full. Do not assume Book is deposit-only. Terminal hand quotes may omit a package.

## MyRental can request extend; no Terminal damage charge
MyRental may request to extend the reservation window. Book does not. Terminal has no damage-charge UI; damage write-up is Filament.

## Extend and return UI vs damage notes
MyRental extend always requotes and waits on the package confirm bar. Terminal extend UI includes keep-owed vs requote. Return UI may mark the bike not in service or open blocking service. No damage fee.

## CFD ticket vs Screen day board
Cfd pages show only the paired Terminal's open ticket from Pinia (fed by the CFD channel). Screen and Terminal use the location-channel day store. CFD has no staff controls. Book and MyRental do not subscribe to shop-floor channels unless a later decision says so.

## Screen does not render PII or money
Screen pages must not render customer display name, owed, or paid even though Pinia may contain them. Terminal may. CFD shows the paired ticket only.

## Terminal page tree, no Pad
Pages live under Terminal/, not Station/ or Pad/. Counter PCs and floor tablets use the same Terminal app.
