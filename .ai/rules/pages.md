---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Inertia surfaces by page tree
Pages live under resources/js/pages/{Book,MyRental,Terminal,Cfd,Screen}/. Do not add a Pad page tree. Do not put shop configuration screens in Inertia; those belong in Filament at /manage. Surface first paint is Inertia; writes go through Action JSON.

## Guest Book, MyRental session and signed URL
Book does not require a Customer session to browse or take a provisional. After reserve, start a Customer session on that browser and keep them signed in. Also email a signed MyRental URL, show it once on Book, and let Terminal re-send. URL and MyRental access last until 3 days after ends_at. Do not build a Customer password gate. Name, email, and phone are required on Book and Terminal. Book reuses a Customer on email match. Do not add a customer cancel control. Show a specific-bike picker on Book only when Location policy is book_may_pin (label with bid). Catalog photos use bike, else variant, else model.

## No ops /t/ URLs in the SPA
Surface links use unprefixed tenant-host paths via Wayfinder. Do not send users to /t/{tenant}/….

## Outstanding balance reminders
When owed != paid, remind staff to collect or refund at pickup, return, and whenever a Reservation or Customer is shown on Terminal or MyRental. MyRental may take a Connect remainder payment. Do not hide the record. Do not put a customer self-refund or self-cancel control on Book or MyRental. Terminal cancel UI prompts to confirm Out bikes are in the shop; do not auto-return them. Book Confirmed follows the package confirm threshold, not a global pay-in-full or deposit-only rule.

## Book pays the package threshold, not always full
Book always uses a package. If none are Book-visible, or Location hours are empty, show an empty not-configured state; do not invent a demo catalog or shop hours. Self-serve Confirmed when paid meets that package's confirm threshold (none, deposit, or full), capturing that amount immediately on Connect. Do not assume Book is pay-in-full. Do not assume Book is deposit-only. If Express cannot charge, hide pay unless the threshold is $0/none. Terminal hand quotes may omit a package. Book (and MyRental extend) pickers must place starts_at and ends_at each during a Location open interval, not on closed dates; an interval may close after midnight; overnight spanning a closed night is allowed. Terminal and Filament interval UI may override.

## MyRental can request extend; no Terminal damage charge
MyRental may request to extend the reservation window. Book does not. Terminal has no damage-charge UI; damage write-up is Filament.

## Extend and return UI vs damage notes
MyRental extend always requotes and waits on the package confirm bar. Terminal extend UI includes keep-owed vs requote. Late return UI prompts the same choice; requote uses the interval through return time. Early return does not lower owed. Return UI may mark the bike not in service or open blocking service. When Location uses back, Terminal has a put-away control to home. Counter and Manager may refund and park bikes on Terminal. No damage fee. Terminal may re-send the MyRental signed URL.

## CFD ticket vs Screen day board
Cfd pages show only the paired Terminal's open ticket from Pinia (fed by the CFD channel). Terminal sets focus (walk-in / pay / pickup / return); clearing focus or idle empties the CFD. The customer may edit name, email, and phone on the CFD; Terminal and CFD patch those fields live both ways. Waiver in v1 is a timestamped checkbox on the reservation; stored signature is later. CFD has no staff controls. Screen uses the location-channel day store. Terminal uses that store and also subscribes to its paired CFD channel. Book and MyRental do not subscribe to shop-floor channels unless a later decision says so.

## Screen does not render PII or money
Screen pages must not render customer display name, owed, or paid even though Pinia may contain them. Terminal may. CFD shows the paired ticket only.

## Terminal page tree, no Pad
Pages live under Terminal/, not Station/ or Pad/. Counter PCs and floor tablets use the same Terminal app. Design it as a restaurant P.O.S.: large buttons, dense actions, one-finger taps (other hand holding a tablet, or a fixed counter screen). Do not use usual marketing-web or Filament chrome (small links, hover-only, long forms). Training is allowed; latency and extra taps are not.

## CFD customer fields sync both ways
The customer may edit name, email, and phone on the CFD. Terminal and CFD patch those fields live. Waiver in v1 is waiver_accepted_at; stored signature is later. CFD has no staff controls.

## Book not-configured without hours
Book shows the empty not-configured state when there is no Book-visible package or Location hours are empty. Counter and Manager may refund and park bikes on Terminal. Early return UI does not lower owed.

## Photo fallback and bid labels
Book and Terminal show catalog photos with fallback bike then variant then model. Terminal and Screen label a bike by bid. Do not scan barcode, RFID, or QR in v1.
