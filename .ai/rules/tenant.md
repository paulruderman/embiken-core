---
paths:
  - 'database/migrations/tenant/**'
---

# Tenant

## Staff password reset tokens on the tenant database
Staff set-password invites use Password::broker('staff'), which writes password_reset_tokens on the tenant connection. Include that table in database/migrations/tenant. The central users migration's copy is not applied to shop databases.
