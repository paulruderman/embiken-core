---
paths:
  - 'app/Http/**'
---

# Http

## Actions are the HTTP controllers
Route use cases to Action classes, not Http\Controllers. Do not add Form Requests or wrapper controllers when asController can serve. Keep Http for middleware and Inertia shared props. JSON asController responses use API Resources. Never pass Request into handle().

## Four auth kinds; Device pairing later
Platform users are central. Staff and customers are tenant models. Shop-operable Terminal is a Staff password session only. Do not require a Device row or Sanctum device token for /terminal. Device pairing (Device bound to Location + surface, token-authenticated) is a later slice; then Terminal becomes staff plus device. When CFD and Screen exist they must not use a staff password in daily operation.

## Occupancy and tenant connection
Do not count occupancy in asController; go through App\Services\Availability in handle(). Never query tenant models on the central connection.

## Guards, guest Book; Device pairing later
Separate guards: platform User, Staff, Customer. Staff authenticate with a password after a set-password invite; do not paste a password on tenant create. Shop-operable Terminal requires staff session only. Sanctum token on Device (not Staff) and Filament pairing codes wait for the Device slice. Book is guest until checkout allocate; then create a Customer session on that browser (remember the browser; do not force a password). `/book` resumes that reservation (Provisional → checkout, Confirmed → receipt) until ends_at + 3 days. MyRental also uses a signed URL, valid until 3 days after ends_at. Mint the token once; show it on Book Confirmed whenever the session resumes; do not email it in shop-operable v1; let Terminal reveal. Do not build a Customer password gate on Book in v1.

## Customer when known, not only on successful pay
Attach a Customer when the person is known (online pay or staff confirm), including staff Confirmed after a failed Connect charge. Name, email, and phone are required on Book and Terminal. Book reuses a Customer on email match and updates name/phone; do not force-create on Book. Terminal matches on email and phone and may force-create a new row. Do not wait for a successful PaymentIntent to create the Customer.

## MyRental remainder and extend
MyRental may pay outstanding owed minus paid via Connect (same 3% fee). MyRental extend is a customer write: authorize the Customer session or signed URL (until ends_at plus 3 days), then ExtendAction. Do not let Book extend or take remainder after Confirmed. Package confirm threshold applies to MyRental extend on the new owed. The new ends_at must still fall during a Location open interval unless staff override.

## Channel auth: location vs CFD
Authorize tenant.{tenantId}.location.{locationId} for staff. Terminal and screen device tokens join that channel in the later Device slice. Authorize tenant.{tenantId}.cfd.{cfdDeviceId} (later) for that CFD device and for the paired Terminal device (staff plus that terminal device). Do not authorize Book, MyRental, or Screen on the CFD channel.

## Book charges the Express connected account
Book PaymentIntents destination-charge the shop's Express account with 3% application_fee_amount and capture the confirm-threshold amount immediately. Do not use a shop-provided secret key. If the connected account cannot charge yet, disable Book pay unless the package confirm threshold is none/$0 (then Confirmed is allowed with no PaymentIntent).

## Paired Terminal on the CFD channel (later)
When the Device slice lands, authorize the CFD device and the paired Terminal device on tenant.{tenantId}.cfd.{cfdDeviceId}. Do not authorize Screen, Book, or MyRental on that channel. Shop-operable Terminal does not subscribe to a CFD channel.

## Staff set-password invite
Staff authenticate with a password after a set-password invite. Do not paste a password when creating the tenant.

## Terminal HTTP is staff session only
Shop-operable /terminal requires a Staff session. Do not require a Device row or Sanctum device token. Pairing codes and device tokens wait. Location channel auth is staff; CFD channel auth waits.

## Impersonation is tenant-host Staff; Suspend blocks Staff login
User impersonation redirects to the tenant host as the Platform Manager (Staff session). Apex User session pauses; Exit returns to Platform Filament. While Suspended, Staff password sign-in on the shop host is refused; impersonation still works. Do not serve shop surfaces at /t/{tenant}/… in shop-operable v1.

## If Express cannot charge, only $0/none Book packages
If Express cannot charge, hide Book pay and offer only packages whose confirm threshold is $0/none. If none of those exist, Book shows online checkout isn’t available — not not-configured, not Suspended. Do not let the customer build a cart they cannot confirm.

## Tenant hosts initialize tenancy on the web stack
Livewire update/upload routes only use the web group, not shop Filament panel middleware. Append InitializeTenancyByDomainIfTenantHost to web and prepend it in middleware priority so tenant hosts (demo.localhost) switch to the shop DB before StartSession and staff login. Skip when the host is in tenancy.central_domains so Platform Filament on the apex stays central. Do not put PreventAccessFromCentralDomains on those Livewire routes.

## Terminal prototype skips Inertia SSR
Keep /prototype/terminal in HandleInertiaRequests $withoutSsr. That page hydrates Pinia and subscribes to Echo; SSR of useEcho opens Pusher during setup and the client remounts in a /broadcasting/auth loop. Book and other guest pages may still SSR.
