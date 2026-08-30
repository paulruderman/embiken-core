---
paths:
  - 'app/Http/**'
---

# Http

## Actions are the HTTP controllers
Route use cases to Action classes, not Http\Controllers. Do not add Form Requests or wrapper controllers when asController can serve. Keep Http for middleware and Inertia shared props. Never pass Request into handle().

## Four auth kinds and surface devices
Platform users are central. Staff and customers are tenant models. Devices are not users: a Device row bound to a Location and surface, token-authenticated. CFD and screen must not use a staff password in daily operation. Terminal uses staff plus a bound device.

## Occupancy and tenant connection
Do not count occupancy in asController; go through Availability in handle(). Never query tenant models on the central connection.

## Guards, device pairing, guest Book
Separate guards: platform User, Staff, Customer. Sanctum token on Device, not on Staff. Terminal requires staff session plus device token. Pairing: one-time Filament code redeemed for a token; do not copy long-lived tokens. Book is guest until reserve; MyRental uses a signed or magic link.

## Customer when known, not only on successful pay
Attach a Customer when the person is known (online pay or staff confirm), including staff Confirmed after a failed Connect charge. Do not wait for a successful PaymentIntent to create the Customer.

## MyRental extend is a customer Action
MyRental extend is a customer write: authorize the magic/signed link, then ExtendAction. Do not let Book extend. Package confirm threshold applies to MyRental extend on the new owed.

## Channel auth: location vs CFD
Authorize tenant.{tenantId}.location.{locationId} for staff and for terminal and screen devices. Authorize tenant.{tenantId}.cfd.{cfdDeviceId} only for that CFD device. Do not authorize Book or MyRental on shop-floor channels.

## Book charges the Express connected account
Book PaymentIntents destination-charge the shop's Express account with 3% application_fee_amount. Do not use a shop-provided secret key.
