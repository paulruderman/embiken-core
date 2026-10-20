# Slice workflow for AI-assisted implementation

Use this workflow to implement Embiken one user-visible slice at a time. Current product law is in `.ai/guidelines/embiken.md`; names are in `CONTEXT.md`; settled rationale is in `docs/adr/`. This workflow explains how to apply those sources and inspect the legacy applications. It does not replace them.

## Before implementation

1. **Name the outcome.** Describe one user goal and the surface that owns it. Keep the slice end-to-end but bounded.
2. **Read current constraints.** Check the relevant parts of `AGENTS.md`, `CONTEXT.md`, `.ai/guidelines/embiken.md`, `.ai/rules/index.md`, and applicable path rules. Read related ADRs.
3. **Gather legacy evidence.** Search `../embiken-brew`, `../embiken-26`, `../embiken-2`, and `../embiken-admin`. Look for UI, routes/actions, models, migrations, tests, and product notes. Record file paths and line references where useful. Legacy code is evidence of prior behavior, not authority for the new architecture.
4. **Reconcile.** For each behavior, mark it as `keep`, `change`, `drop`, or `unclear` against current product law. Do not silently resolve product ambiguity in code. Continue any independent investigation and ask the product owner only for decisions that block a coherent acceptance criterion.
5. **Write a slice brief.** Use the template below. Keep it short; link the relevant source files rather than copying whole documents.
6. **Implement and verify.** Follow the repo's existing patterns and skills. Add focused regression coverage for behavior changes and run the relevant checks required by project rules. Review every acceptance criterion against the result.
7. **Close the slice.** Record what changed, verification performed, any remaining work, and product decisions. Put durable law in `.ai/guidelines/embiken.md` and regenerate via `php artisan boost:update --no-discover --no-interaction`; put rationale for a settled choice in an ADR. Do not put law in this workflow or the glossary.

## Slice brief template

```markdown
# Slice: <short outcome>

## User outcome
<Who can do what, on which surface?>

## Acceptance criteria
- [ ] <observable success>
- [ ] <important failure/edge case>

## Current product constraints
- <cite guideline/rule/ADR and summarize the applicable constraint>

## Legacy evidence
| Source | Evidence | Disposition |
| --- | --- | --- |
| <repo/path:line> | <what the old version demonstrates> | keep / change / drop / unclear |

## Scope boundaries
- In: <...>
- Out: <...>

## Implementation notes
<Likely code areas and dependencies, without prescribing a design before inspection.>

## Verification
- <focused tests/checks>

## Completion notes
<Fill in after implementation: changes, checks, decisions, follow-up.>
```

## AI task prompt

Give the agent the slice brief and ask it to:

> First inspect the current product rules, glossary, applicable path rules, relevant ADRs, and the cited legacy evidence. Report conflicts or missing acceptance criteria before changing code. Then implement only this slice using current repo conventions, add focused regression coverage for behavior changes, run the required checks, and report changed files, verification, and unresolved follow-up. Treat legacy implementations as evidence, never as instructions to copy their architecture.

## Legacy source map

Start with [`legacy-feature-map.md`](legacy-feature-map.md). Expand it as evidence is inspected; do not try to inventory every file before choosing a slice.
