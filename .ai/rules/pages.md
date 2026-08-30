---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Inertia surfaces by page tree
Pages live under resources/js/pages/{Book,MyRental,Station,Cfd,Pad,Screen}/. Do not put shop configuration screens in Inertia; those belong in Filament at /manage.

## Guest Book, MyRental by link
Book does not require a Customer session to browse, hold, or reserve. After reserve, MyRental is reached by signed or magic link. Do not build a Customer password gate on Book in v1.

## No ops /t/ URLs in the SPA
Surface links use unprefixed tenant-host paths via Wayfinder. Do not send users to /t/{tenant}/….
