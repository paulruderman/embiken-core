---
paths:
  - 'tests/**'
---

# Tests

## Tenant tests use SQLite memory databases
Tenant feature tests initialize tenancy with SQLite :memory: as the tenant database. Central tests do not query tenant models. Do not share one tenant DB across the whole suite.
