# Start AI assistance with a staff-only Terminal assistant

## Context

Embiken wants an LLM assistant in Terminal and an MCP interface for reservation work on behalf of both customers and staff. Reservation writes already have a domain boundary in the Bike Rentals Actions, while Terminal routes are tenant-host routes behind both `auth:staff` and `device:terminal`. The staff authorization concern checks for an authenticated Staff member and the suspended-tenant exception; route middleware supplies the paired Terminal Device requirement.

The current customer self-booking surface is not yet a parallel set of reservation Actions in this repository. The visible tenant routes expose the Book page and signed MyRental link access, while the identified customer-facing reservation create/change workflow remains to be implemented. Customer authorization therefore needs its own design rather than reuse of Staff authorization or broad tenant access.

## Decision

Build this in stages, starting with a first-party, staff-only assistant embedded in Terminal. The assistant may answer questions and prepare structured proposals. It must execute a state change only by invoking an existing domain Action (or a new Action added for a real application use case), under the current tenant, Staff, and paired Terminal Device context. The assistant itself does not gain broader permissions than the signed-in Staff member.

Keep MCP as a protocol adapter over an explicit allowlist of application capabilities. Do not expose model access to Eloquent, arbitrary SQL, or general HTTP routes. Authorize each tool invocation against its caller and tenant, not just at connection time. For the first stage, serve MCP capabilities only through the same first-party staff context as Terminal; do not publish a general customer MCP endpoint until customer identity, consent, reservation ownership, and token revocation are designed.

Terminal chat calls a transport-neutral internal tool registry in the first release. Do not expose an external MCP transport in that release. Add MCP as a later adapter over the same registry, preserving the Staff, Device, tenant, role, and per-tool authorization context.

Keep the model integration provider-neutral. Support Ollama as a development backend so the Terminal assistant can be exercised against a locally hosted model. Do not choose or require a production model provider yet. Regardless of provider, minimize model context and exclude customer email and phone. Keep customer contact entry in a structured Terminal form and retain it server-side with the pending draft; the model should refer to that draft by an opaque handle. A production self-hosted service or richer reservation context are later decisions.

Mutations may execute after the staff member explicitly confirms a clear proposal in chat. Chat may later hand off to or prefill existing Terminal screens for workflows where that is a better fit. Existing Action-level validations and confirmations remain authoritative, including the extreme availability override confirmation. Payment, refund, cancel, pickup, return, and other consequential operations are excluded from the initial tool allowlist.

## Initial capability boundary

- **First implementation slice — staff read:** search Reservations and inspect their lines, status, schedule, balance, and bike readiness within the active tenant; check bike availability. Responses sent to the model omit customer name, email, and phone. No write tools in this slice.
- **Next slice — staff proposal:** prepare a walk-in Reservation draft. Collect contact details in a structured Terminal form, and require staff to supply or confirm the hand-entered amount; the model must not invent a quote. Show selected bikes, interval, amount, conflicts, and the customer email side effect before staff confirms.
- **Later staff commit:** on confirmation, invoke `CreateWalkInReservationAction`; do not enable availability overrides in the first write allowlist. Add an idempotent commit boundary so retries cannot create duplicate Reservations or notices.
- **Staff writes, later:** expose an operation only after its role access, required confirmations, idempotency behavior, and customer-visible effects are specified and tested against its existing Action.
- **Customer tools, later:** derive access from the authenticated Book customer session or the specific signed MyRental Reservation link. Limit reads and writes to Reservations that identity controls; retain Book's package, hours, availability, and payment rules. Do not expose customer cancel, refund, staff-only assignment, or fleet operations.

## Consequences

- The shared `AuthorizesStaff` concern establishes Staff identity and the suspended-tenant rule, but it does not distinguish Counter from Manager. The tool allowlist must therefore map each operation's role policy explicitly and enforce it at invocation; a prompt cannot grant Manager privileges to Counter staff.
- Tenant isolation is established from the authenticated request and initialized tenant context, never from an LLM-supplied tenant identifier.
- The first useful slice is narrower than a broad reservation-management MCP server, but it can ship without inventing customer identity semantics or bypassing the existing Terminal security boundary.
- Customer-facing booking tools require a later decision on MCP transport authentication and how a customer session or signed MyRental link is represented by an MCP client.

## Considered options

- **Launch staff and customer MCP tools together** — deferred: customer self-booking Actions and MCP caller identity are not yet defined, so the boundary would be speculative.
- **Let the model call existing HTTP routes directly** — rejected: route access alone does not give the model a typed tool contract, reliable caller context, or a reviewable mutation step.
- **Give the model database or broad service access** — rejected: that bypasses domain Actions, Availability, authorization, and validation.
- **Begin with staff-only Terminal assistance** — selected: Terminal already requires Staff plus a paired Device, and Reservation operations already have domain Actions to reuse.

## User choices recorded

- Keep the LLM integration provider-neutral; support local Ollama for development and defer the production provider decision.
- Minimize model context and exclude customer email and phone for every provider.
- Allow staff to confirm and execute supported changes in chat; consider handoff to existing Terminal screens for selected workflows later.
- Use an internal tool registry for the first Terminal assistant release; add external MCP connectivity later.
- Avoid a production-provider commitment now; support local Ollama for development and revisit hosted or self-hosted inference later.
