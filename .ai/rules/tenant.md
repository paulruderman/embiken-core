---
paths:
  - 'database/migrations/tenant/**'
---

# Tenant

## Staff password reset tokens on the tenant database
Staff set-password invites use Password::broker('staff'), which writes password_reset_tokens on the tenant connection. Include that table in database/migrations/tenant. The central users migration's copy is not applied to shop databases.

## Database sessions live on the tenant connection
SESSION_DRIVER=database uses the default connection. After InitializeTenancyByDomain, that is the tenant DB. Include a sessions table in database/migrations/tenant (Laravel sessions columns; do not FK user_id to central users). Do not point SESSION_CONNECTION at central for shop hosts — Staff and Customer sessions belong in the shop database. Central sessions stay in the central users migration for the apex host.
