# Amend occupancy broadcast after commit

Status: resolved
Type: task

## Question

Write the **already-established** Laravel 13 fact into Boost law: occupancy broadcasts use `ShouldBroadcastNow` **and** `ShouldDispatchAfterCommit`. There is no `ShouldBroadcastAfterCommit` in this framework.

Source: [Reverb Echo Pinia for Terminal day store](03-reverb-echo-pinia-terminal.md) / [findings](../research/reverb-echo-pinia.md).

Update `.ai/rules/events.md` (and guidelines if they repeat the old name) via `record-rule` / Boost update as usual. Do not re-grill. Do not invent DTO fields.

## Answer

Occupancy events implement `ShouldBroadcastNow` and `ShouldDispatchAfterCommit`. Laravel 13 has no `ShouldBroadcastAfterCommit`. Recorded in `.ai/rules/events.md`. Guidelines did not repeat the old name.
