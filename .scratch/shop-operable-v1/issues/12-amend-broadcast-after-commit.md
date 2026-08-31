# Amend occupancy broadcast after commit

Status: open
Type: task

## Question

Write the **already-established** Laravel 13 fact into Boost law: occupancy broadcasts use `ShouldBroadcastNow` **and** `ShouldDispatchAfterCommit`. There is no `ShouldBroadcastAfterCommit` in this framework.

Source: [Reverb Echo Pinia for Terminal day store](03-reverb-echo-pinia-terminal.md) / [findings](../research/reverb-echo-pinia.md).

Update `.ai/rules/events.md` (and guidelines if they repeat the old name) via `record-rule` / Boost update as usual. Do not re-grill. Do not invent DTO fields.
