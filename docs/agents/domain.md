# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT.md`** at the repo root, or
- **`CONTEXT-MAP.md`** at the repo root if it exists: it points at one `CONTEXT.md` per context. Read each one relevant to the topic.
- **`docs/adr/`**: read ADRs that touch the area you're about to work in. In multi-context repos, also check `src/<context>/docs/adr/` for context-scoped decisions.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them upfront. The `/domain-modeling` skill (reached via `/grill-with-docs` and `/improve-codebase-architecture`) creates them lazily when terms or decisions actually get resolved.

**Split:** `CONTEXT.md` is the glossary (what a word means, `_Avoid_`). `.ai/guidelines/embiken.md` is always-on law (must / must-not), synced into `AGENTS.md`. `docs/adr/` is why. Path-scoped law is `.ai/rules` via `record-rule`. Do not copy the glossary into guidelines, or law into `CONTEXT.md`. If `CONTEXT.md` is missing, use Embiken terms already in guidelines (Tenant, Location, Reservation, Bike, Book, Terminal), then add the term to `CONTEXT.md` when it is resolved.

## File structure

Single-context repo (this repo):

```
/
├── CONTEXT.md
├── docs/adr/
└── app/
```

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `CONTEXT.md`. Don't drift to synonyms the glossary explicitly avoids (Booking, Station, Pad, Product table, Allocation). Law (must / must-not) comes from guidelines and `.ai/rules`, not from restating it in the glossary.

If the concept you need isn't in the glossary yet, that's a signal: either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0007 (event-sourced orders), but worth reopening because…_
