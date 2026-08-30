---
paths:
  - 'app/Http/**'
---

# Http

## Actions are the HTTP controllers
Route use cases to Action classes, not Http\Controllers. Do not add Form Requests or wrapper controllers when asController can serve. Keep Http for middleware and Inertia shared props. JSON asController responses use API Resources. Never pass Request into handle().

## Four auth kinds and surface devices
Platform users are central. Staff and customers are tenant models. Devices are not users: a Device row bound to a Location and surface, token-authenticated. CFD and screen must not use a staff password in daily operation. Terminal uses staff plus a bound device.

## Occupancy and tenant connection
Do not count occupancy in asController; go through Availability in handle(). Never query tenant models on the central connection.

## Guards, device pairing, guest Book
Separate guards: platform User, Staff, Customer. Sanctum token on Device, not on Staff. Terminal requires staff session plus device token. Pairing: one-time Filament code redeemed for a token; do not copy long-lived tokens. Book is guest until reserve; then create a Customer session on that browser (remember the device; do not force a password). MyRental also uses a signed URL, valid until 3 days after ends_at. Email the URL, show it once on Book after reserve, and let Terminal re-send. Do not build a Customer password gate on Book in v1.

## Customer when known, not only on successful pay
Attach a Customer when the person is known (online pay or staff confirm), including staff Confirmed after a failed Connect charge. Name, email, and phone are required on Book and Terminal. Do not wait for a successful PaymentIntent to create the Customer.

## MyRental remainder and extend
MyRental may pay outstanding owed minus paid via Connect (same 3% fee). MyRental extend is a customer write: authorize the Customer session or signed URL (until ends_at plus 3 days), then ExtendAction. Do not let Book extend or take remainder after Confirmed. Package confirm threshold applies to MyRental extend on the new owed. The new ends_at must still fall during Location open hours unless staff override.

## Channel auth: location vs CFD
Authorize tenant.{tenantId}.location.{locationId} for staff and for terminal and screen devices. Authorize tenant.{tenantId}.cfd.{cfdDeviceId} only for that CFD device. Do not authorize Book or MyRental on shop-floor channels.

## Book charges the Express connected account
Book PaymentIntents destination-charge the shop's Express account with 3% application_fee_amount and capture the confirm-threshold amount immediately. Do not use a shop-provided secret key. If the connected account cannot charge yet, disable Book pay unless the package confirm threshold is none/$0 (then Confirmed is allowed with no PaymentIntent).
