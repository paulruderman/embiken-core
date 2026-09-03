---
paths:
  - CONTEXT.md
  - vite.config.ts
---

# General

## CONTEXT.md is glossary, not law
CONTEXT.md is the glossary only: what a word means and _Avoid_. No must/must-not, no flows, no Filament/Action details. Law belongs in .ai/guidelines/embiken.md (then boost:update). Path-scoped law via record-rule. Why belongs in docs/adr/. Do not copy glossary terms into embiken.md or duplicate law into CONTEXT.md.

## Vite must not watch SQLite or logs
Ignore storage/**, *.log, *.sqlite, and database/tenant* in Vite server.watch. Shop sessions live in the tenant SQLite file (database/tenant{uuid}, no extension). Every request writes that file; Tailwind's Vite plugin then full-reloads the document. Boost browser.log is the same loop. Restart npm run dev after changing watch.ignored.
