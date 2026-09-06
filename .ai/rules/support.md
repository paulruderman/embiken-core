---
paths:
  - app/Support/TenantMigrationPaths.php
---

# Support

## TenantMigrationPaths is the tenant schema list
This helper is the only list of tenant migration directories: realpath host database/migrations/tenant plus every installed module tenantMigrationPaths(). Do not skip modules that are installed but not enabled.
