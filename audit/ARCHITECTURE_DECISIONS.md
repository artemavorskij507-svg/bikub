# ARCHITECTURE_DECISIONS

- STATUS: IN PROGRESS

## ADR-001: Keep heuristic ETA as production decision path
- Decision: Heuristic ETA remains authoritative for dispatch decisions.
- Why: Routing provider availability and quality baseline are not yet stable enough for hard dependency.
- Impact: Routing runs in shadow mode only; no dispatch hard-fail on provider outage.

## ADR-002: Routing provider degradation is warn-only
- Decision: Provider health failure produces WARN, not FAIL, in runtime smoke.
- Why: Ops continuity must not depend on external routing provider uptime at this phase.
- Impact: Runtime smoke remains green while preserving observability signal.

## ADR-003: Org scope resolver must be canonical and non-int-cast
- Decision: Organization scope resolved through dedicated action and treated as string-safe value.
- Why: UUID/int drift previously caused runtime errors and potential data leakage risks.
- Impact: Policies/controllers rely on normalized scope source.

## ADR-004: API stub endpoints must fail safe (501 JSON)
- Decision: Incomplete controller actions return controlled `501 Not Implemented` JSON.
- Why: Prevent internal 500 errors and maintain predictable API contracts.
- Impact: Better client behavior and clearer backlog for unfinished APIs.

## ADR-005: Event listener references must be concrete at boot-time
- Decision: Every listener referenced in provider must exist with safe handling.
- Why: Missing listeners caused runtime crashes in queues/event flow.
- Impact: Startup/runtime stability and traceable event pipeline.

## ADR-006: Governance artifacts are mandatory, not optional
- Decision: Program runs with persistent artifacts (`MASTER_STATUS`, `ROUTES_AUDIT`, `ADMIN_MODULES_AUDIT`, `CODEBASE_REVIEW`, `BUG_REGISTER`, `BACKLOG`, `HANDOFF`).
- Why: Scope is too large for single-pass memory-only execution.
- Impact: Repeatable continuation across cycles with measurable progress.

## ADR-007: Legacy OrderResource create flow is deprecated in favor of DeliveryOrderResource
- Decision: Disable `OrderResource` create entry points (`canCreate=false`, remove create route/action) until schema-safe create flow is guaranteed.
- Why: Production `admin/orders/create` returns 500 and blocks delivery admin operations.
- Impact: Prevents operators from entering broken create path; order creation remains available through domain-specific delivery flow.

## Open decision topics
1. Final HTTPS-only enforcement approach with proxy and cookie policies.
2. Controlled routing influence rollout (post shadow baseline only).
3. Canonical release branch/workspace strategy to avoid non-git drift.
