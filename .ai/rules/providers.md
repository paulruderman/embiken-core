---
paths:
  - app/Providers/TenancyServiceProvider.php
---

# Providers

## Never drop tenant databases on TenantDeleted
TenantDeleted must not run DeleteDatabase. Tenant delete removes central Tenant and Domain rows only.
