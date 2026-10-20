---
name: feature-slices
description: "Use when implementing or materially changing a user-visible Embiken feature. Helps scope work into a reviewable slice, reconcile legacy behavior with current product rules, and verify the completed outcome."
---

# Embiken feature slices

For feature implementation in `embiken-core`, follow [`docs/ai/slice-workflow.md`](../../../docs/ai/slice-workflow.md). Use [`docs/ai/legacy-feature-map.md`](../../../docs/ai/legacy-feature-map.md) to find relevant prior behavior in the sister repositories.

Before editing:

1. State the user outcome and bounded slice. If the request lacks observable acceptance criteria, draft concise criteria from the request and current product rules; ask only about unresolved product decisions that block implementation.
2. Read the relevant current law in `AGENTS.md`, `CONTEXT.md`, `.ai/guidelines/embiken.md`, matching `.ai/rules/`, and applicable ADRs. Follow the repo's rule-index process before editing.
3. Inspect relevant legacy UI, routes/actions, models, and tests when they can inform the behavior. Record what is evidence versus inference, and mark behavior `keep`, `change`, `drop`, or `unclear` against current rules. Never copy legacy architecture just because it exists.
4. Summarize the brief and any conflicts before implementation. If the brief is already clear in the user's request, keep this summary compact and proceed.

Implement only the coherent slice, following current application patterns and applicable skills. Add focused regression coverage when behavior changes and verify according to repository instructions. Before finishing, check each acceptance criterion, report the relevant legacy evidence and disposition, list verification performed, and identify unresolved follow-up. Record durable product law or settled rationale in its designated source as described by the workflow.
