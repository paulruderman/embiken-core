---
paths:
  - 'config/**'
---

# Config

## Provisional TTL is config not Location
Provisional cart TTL minutes live in config('embiken.provisional_ttl_minutes') default 15 (EMBIKEN_PROVISIONAL_TTL_MINUTES). Do not put TTL minutes on Location. Reservations still store expires_at.
