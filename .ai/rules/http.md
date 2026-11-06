---
paths:
  - 'app/Http/**'
---

# Http

## Actions are the HTTP controllers
Route use cases to Action classes, not Http\Controllers. Do not add Form Requests or wrapper controllers when asController can serve. Keep Http for middleware and Inertia shared props. JSON asController responses use API Resources. Never pass Request into handle().

## Platform, Staff, Customer, and Device identities
Platform Users are central. Staff, Customers, and Devices are tenant models. Terminal requires a Staff password session and paired Terminal Device. Customer Display and Wall Display use paired Device authentication only. Device is not a user; Sanctum tokens belong to Device.

## Occupancy and tenant connection
Do not count occupancy in asController; go through App\Services\Availability in handle(). Never query tenant models on the central connection.

## Guards, guest Book, and paired Devices
Separate guards: platform User, Staff, Customer; Device tokens use Sanctum and never a Staff guard. Staff authenticate with a password after a set-password invite; do not paste a password on tenant create. Device pairing uses short-lived one-time codes entered in Manager-only Shop Filament. Book is guest until checkout allocate; then create a Customer session on that browser (remember the browser; do not force a password). `/book` resumes that reservation (Provisional → checkout, Confirmed → receipt) until ends_at + 3 days. MyRental uses a revocable signed URL containing the persistent reservation token and expiring three days after ends_at. Reservation notices and email re-requests send the same link only to the reservation email. Re-request returns a generic response, including when no eligible reservation exists. Managers can revoke a Customer's links from Filament. Do not add Customer passwords in v1.

## Customer when known, not only on successful pay
Attach a Customer when the person is known (online pay or staff confirm), including staff Confirmed after a failed Connect charge. Name, email, and phone are required on Book and Terminal. Book reuses a Customer on email match and updates name/phone; do not force-create on Book. Terminal matches on email and phone and may force-create a new row. Do not wait for a successful PaymentIntent to create the Customer.

## MyRental remainder and extend
MyRental may pay outstanding owed minus paid via Connect (same 3% fee). MyRental extend is a customer write: authorize the Customer session or signed URL (until ends_at plus 3 days), then ExtendAction. Do not let Book extend or take remainder after Confirmed. Package confirm threshold applies to MyRental extend on the new owed. The new ends_at must still fall during a Location open interval unless staff override.

## Channel auth: location vs Customer Display
Authorize tenant.{tenantId}.location.{locationId} for Staff, paired Terminal Devices, and Wall Display Devices. Authorize tenant.{tenantId}.cfd.{deviceId} for that Customer Display Device and its paired Terminal Device. Do not authorize Book, MyRental, or Wall Display on the Customer Display channel.

## Book charges the Express connected account
Book PaymentIntents destination-charge the shop's Express account with 3% application_fee_amount and capture the confirm-threshold amount immediately. Do not use a shop-provided secret key. If the connected account cannot charge yet, disable Book pay unless the package confirm threshold is none/$0 (then Confirmed is allowed with no PaymentIntent).

## Paired Terminal on the Customer Display channel
Authorize the Customer Display Device and its paired Terminal Device on tenant.{tenantId}.cfd.{deviceId}. Do not authorize Wall Display, Book, or MyRental on that channel.

## Staff set-password invite
Staff authenticate with a password after a set-password invite. Do not paste a password when creating the tenant.

## Terminal HTTP requires staff and paired Device
/terminal requires both a Staff session and paired Terminal Device. Impersonated Platform Manager Staff must also use the paired Terminal Device. Location channel auth follows the Staff/Terminal/Wall Display rules above.

## Impersonation is tenant-host Staff; Suspend blocks Staff login
User impersonation redirects to the tenant host as the Platform Manager (Staff session). Apex User session pauses; Exit returns to Platform Filament. While Suspended, Staff password sign-in on the shop host is refused; impersonation still works. Do not serve shop surfaces at /t/{tenant}/… in shop-operable v1.

## If Express cannot charge, only $0/none Book packages
If Express cannot charge, hide Book pay and offer only packages whose confirm threshold is $0/none. If none of those exist, Book shows online checkout isn’t available — not not-configured, not Suspended. Do not let the customer build a cart they cannot confirm.

## Tenant hosts initialize tenancy on the web stack
Livewire update/upload routes only use the web group, not shop Filament panel middleware. Append InitializeTenancyByDomainIfTenantHost to web and prepend it in middleware priority so tenant hosts (demo.localhost) switch to the shop DB before StartSession and staff login. Skip when the host is in tenancy.central_domains so Platform Filament on the apex stays central. Do not put PreventAccessFromCentralDomains on those Livewire routes.

## Terminal prototype skips Inertia SSR
Keep /prototype/terminal in HandleInertiaRequests $withoutSsr. That page hydrates Pinia and subscribes to Echo; SSR of useEcho opens Pusher during setup and the client remounts in a /broadcasting/auth loop. Book and other guest pages may still SSR.

## Page rendering and Actions
HTTP controllers may render static or editorial Inertia pages. Use Actions for application use cases, not solely as URL adapters or page renderers; do not add thin controllers around Actions that already own a meaningful use case.

## Actions are the HTTP controllers
Use Actions as HTTP controllers for meaningful application use cases with behavior, authorization, validation, state changes, or orchestration. Use ordinary page routes/controllers for static or editorial page rendering; do not create Actions solely for a URL, GET, or Inertia render. Do not add thin controllers around Actions that already own a use case.
