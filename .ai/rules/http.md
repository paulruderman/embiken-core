---
paths:
  - 'app/Http/**'
---

# Http

## Invoke domain Actions for writes
Mutating HTTP use cases authorize and validate in Form Requests and policies, then invoke an Action as a callable. Method-inject the Action. Never pass Request into handle().

## Four auth kinds and surface devices
Platform users are central. Staff and customers are tenant models. Devices are not users: a Device row bound to a Location and surface, token-authenticated. CFD and screen must not use a staff password in daily operation. Station and pad use staff plus a bound device.

## Occupancy and tenant connection
Do not count occupancy in controllers; go through Availability via an Action. Never query tenant models on the central connection.

## Guards, device pairing, guest Book
Separate guards: platform User, Staff, Customer. Sanctum token on Device, not on Staff. Station and pad require staff session plus device token. Pairing: one-time Filament code redeemed for a token; do not copy long-lived tokens. Book is guest until reserve; create Customer at reserve; MyRental uses a signed or magic link.
