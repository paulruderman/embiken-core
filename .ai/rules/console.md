---
paths:
  - 'app/Console/**'
---

# Console

## No Console Command classes for domain Actions
Do not add app/Console/Commands for a domain use case. Use $commandSignature and asCommand on the Action. Kernel or console routes only register or schedule those Actions.

## Prompts for missing Action command input
Domain commands live on Actions. Missing asCommand input uses laravel/prompts unless --no-interaction. Tenant commands require --tenant or an interactive tenant prompt. Do not add Console\Command classes that reimplement this.
