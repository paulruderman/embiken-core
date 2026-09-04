---
paths:
  - 'app/Enums/**'
---

# Enums

## Rich Filament enums with frontend meta
Domain enums are PascalCase cases with snake/int values. Implement Filament HasLabel/HasColor/HasDescription/HasIcon plus HasFrontendMeta; share lookup tables from HandleInertiaRequests as page.props.enums. Vue production surfaces read labels/colors via lib/enums helpers or TS value consts in types/domain.ts — do not duplicate presentation maps for production UI.
