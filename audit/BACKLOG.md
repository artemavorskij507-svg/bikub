# BACKLOG

- STATUS: ACTIVE

## P0 (Critical)
1. [DONE] Complete authenticated `/api/ops/*` read+write smoke with Sanctum-valid auth context.
2. [DONE] Enforce HTTPS-only production mode:
   - DONE: HTTP->HTTPS redirect.
   - DONE: trusted CA certificate issued and deployed for `136.119.84.22.nip.io` (Let's Encrypt).
   - DONE: `APP_URL` switched to trusted HTTPS hostname.
   - DONE: secure session cookies retained.
   - NOTE: direct `https://136.119.84.22` remains non-trusted by design; canonical entrypoint is `https://136.119.84.22.nip.io`.
3. [IN PROGRESS] Validate payment critical path (init/confirm/fallback) with idempotency replay evidence:
   - DONE: fixed public checkout replay duplicates (`/api/v1/public/orders` idempotency).
   - DONE: fixed `payment/intent` 500 and added idempotent replay support.
   - DONE: formalized `/orders/{id}/payment/confirm` contract (422 actionable + idempotent replay + 409 payload conflict).
   - DONE (code): hardened Vipps callbacks/fallback topology and JSON validation contracts:
     - corrected merchant callback prefixes to `/api/v1/payments/vipps/*`,
     - added consent-removal and shipping-details endpoints,
     - added web fallback route `/order/{orderId}/vipps/fallback`,
     - switched not-configured response to explicit `503` with required config keys.
   - DONE (code): added runtime smoke gate `runtime_vipps_readiness` (warn-only) with missing `VIPPS_*` keys + local init probe evidence.
   - DONE (code): added dedicated `ops:payment-readiness` command (Vipps/Stripe config + endpoint probes + JSON report) for deterministic payment gate verification.
   - DONE (code): configured canonical `services.vipps` and `services.stripe` mappings + scheduled hourly readiness run in `Console\\Kernel`.
   - DONE (code): added feature test `OpsPaymentReadinessCommandTest`.
   - REMAINS: configure live Vipps credentials.
4. [DONE] Re-run critical route/module audits under new HTTPS baseline and confirm non-regression.
5. [DONE] Production log sweep for residual `production.ERROR` in Operations/Delivery paths after transport changes.

## P1 (High)
6a. [IN PROGRESS] Close `/executor` 500 regression for authenticated non-executor users.
   - DONE (code): added null-guard in `ExecutorDashboardController::index()` with safe redirect.
   - REMAINS: deploy and re-verify runtime route behavior.
6. [IN PROGRESS] Decide contract for `/api/v1/public/slots` (JSON endpoint vs redirect) and align implementation.
   - DONE (code): endpoint validation switched to explicit JSON 422 contract (`Validation failed` + field errors), added feature test.
   - REMAINS: deploy to server and re-verify runtime response (currently production still shows 302 when `date` is missing).
7. Full RBAC UAT matrix for `admin`, `ops_admin`, `ops_manager`, `ops_rules_admin` on full module list (not only core 7 pages).
8. Extend admin module functional audit from 48 critical domain modules to complete user requirement catalog.
9. Execute functional write checks per audited module (create/edit/delete/bulk/filters).
10. Close preview-vs-live diagnostics parity verification for workbench.
11. [IN PROGRESS] Complete top-priority file transitions in `CODEBASE_REVIEW_REGISTRY.csv` for ops + delivery + auth core.
12. Expand E2E suite for client, executor, admin critical flows.

## P2 (Medium)
13. Build full launch risk register with mitigations and owners.
14. Add backup/restore drill evidence and recovery-time assumptions.
15. Add deployment gate artifact bundle (smoke report + metrics + rollback plan).
16. Harden observability dashboards for ops/errors/queue/retries.
17. Consolidate duplicate legacy pages/routes where still present.
18. Add accessibility sweep for public category pages.
19. Add localized Narvik content consistency check (currency/legal/trust blocks).
20. Prepare final launch-readiness executive report with measurable KPIs.

## Deferred (after core readiness)
21. Controlled routing influence experiments behind feature flags.
22. Advanced auto-replan (manual apply first, auto only after confidence gates).
