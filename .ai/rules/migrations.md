---
paths:
  - 'database/migrations/**'
---

# Migrations

## Tenant migrations, portable schema
Tenant tables live in database/migrations/tenant. Schema must build on every Laravel-supported database: portable types and indexes, no engine-specific DDL.

## Tenant DB provisioned, never auto-dropped
Tenant schema lives in database/migrations/tenant and is applied when a Tenant is created. Do not add migrations or hooks that drop tenant databases on tenant delete.
