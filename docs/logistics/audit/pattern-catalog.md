# Logistics Pattern Catalog (Wave 1)

Date: 2026-03-31  
Role: PatternExtractor  
Scope: Synthesis of architectural and business patterns from available audit and logistics analysis documents.

## Inputs Used
- `COMPLETE_AUDIT_REPORT.md`
- `bikube_audit_report.md`
- `bikube/docs/DELIVERY_MODULE.md`
- `bikube/docs/GEO_ROUTES.md`
- `bikube/docs/PRICE_ENGINE.md`
- `bikube/docs/KASSAL_INTEGRATION.md`
- `bikube/docs/TEST_SCENARIOS.md`
- `bikube/docs/PROJECT_COMPLETION_REPORT.md`

## Adoption Priority Scale
- `P0`: Mandatory before Wave 2/3 implementation work (security, integrity, contract stability)
- `P1`: Adopt in architecture completion and early implementation
- `P2`: Adopt after core stabilization

## Architectural Patterns

| ID | Pattern | Problem Solved | Current Signal in Audits | Priority | Adoption Action |
|---|---|---|---|---|---|
| A1 | Secure-by-default API boundary (`auth:sanctum` + policy/role gates) | Prevents unauthorized mutation and data exposure | Critical gaps identified (public mutating v1 routes, IDOR, duplicate order routes) | P0 | Enforce auth for all mutating endpoints; remove public duplicates; add ownership policies |
| A2 | API contract integrity (no stubbed controllers, route-method parity) | Prevents runtime 500s and broken integrations | High risk found: routes pointing to missing/placeholder methods, GDPR mismatch | P0 | Freeze public API to implemented methods only; add contract tests for every public route |
| A3 | Unified order aggregate with polymorphic service payloads | Single lifecycle across grocery/food/bulky and other service domains | Strongly present in delivery and completion docs | P1 | Keep shared `orders` backbone; formalize service extension contract for new logistics types |
| A4 | Service-layer orchestration (domain services over fat controllers) | Keeps business logic testable and reusable | Present via `TariffCalculator`, `GeoZoneService`, `RoutingService`, factory/services | P1 | Standardize service interfaces and move remaining controller logic into services |
| A5 | Provider strategy + graceful fallback (OSRM/Mapbox/internal) | Resilience when external routing provider fails | Documented routing provider fallback and cache strategy | P1 | Preserve provider abstraction; add provider health metrics and fallback alerts |
| A6 | Event-driven async pipeline (jobs/events/queues) | Decouples order intake from heavy processing and realtime updates | Present (`OrderCreated`, `OrderUpdated`, queue jobs) | P1 | Add idempotency keys and retry policies for logistics jobs |
| A7 | Rule engine for pricing (priority-ordered rules + context object) | Flexible tariff updates without code deploys | Present and mature in Price Engine docs | P1 | Keep DB-driven rule model; add rule validation tests and rollout controls |
| A8 | Multi-layer caching (Geo, routing, pricing, homepage) | Controls latency and external API load | Widely documented with TTL strategies | P1 | Define cache invalidation ownership and consistent TTL policy by domain |
| A9 | Idempotent external catalog sync via external IDs (`kassal_id`) | Safe upserts from partner systems | Present in Kassal integration docs | P1 | Keep upsert-by-external-id and add dead-letter/error replay flow |
| A10 | Runtime compatibility gate in CI (PHP/runtime parity) | Prevents non-runnable builds and blocked tests | High risk found: runtime mismatch blocks artisan/tests | P0 | Pin PHP/runtime versions in CI and deployment; fail pipeline on mismatch |

## Business Patterns

| ID | Pattern | Business Value | Current Signal in Audits | Priority | Adoption Action |
|---|---|---|---|---|---|
| B1 | Multi-service local marketplace under one order platform | Expands revenue via grocery, food, bulky, handyman, eco, social, errands | Strongly present across completion/test docs | P1 | Preserve one customer account/order history across all services |
| B2 | Zone-based operations and pricing segmentation | Enables local economics and SLA realism per geography | Strong GeoZone + zone metadata + price hints | P1 | Make zone metadata the single source for eligibility, SLA, and pricing group |
| B3 | ETA/SLA-centric delivery promise with realtime tracking | Improves customer trust and dispatch quality | ETA logic + realtime tracking components documented | P1 | Standardize ETA calculation path and track ETA accuracy KPI |
| B4 | Demand-responsive pricing (surge multipliers) | Balances capacity and demand peaks | Implemented in demand refresh + multipliers | P1 | Add governance caps/floors and customer-facing surcharge transparency |
| B5 | Substitution policy for grocery fulfillment | Improves fill-rate and order completion | Documented substitution flow and AI-assisted substitution job | P2 | Add explicit customer preference profiles and substitution audit trail |
| B6 | Partner ecosystem ingestion (stores/restaurants external sync) | Scales supply-side coverage quickly | Present via Kassal sync + seeded real partners | P1 | Add freshness SLAs and partner data quality scorecards |
| B7 | Self-service customer portal + operations backoffice | Reduces support load and improves operational control | Strongly present in test scenarios and completion docs | P1 | Keep parity between customer-visible states and admin workflow states |
| B8 | Compliance-by-design for personal data flows (GDPR endpoints, retention) | Reduces legal/regulatory exposure | Intended, but implementation mismatch found in audit | P0 | Align GDPR routes/controllers and add end-to-end compliance tests |

## Wave 1 Adoption Sequence

1. `P0` foundation: A1, A2, A10, B8  
2. `P1` architecture hardening: A3, A4, A5, A6, A7, A8, A9, B1, B2, B3, B4, B6, B7  
3. `P2` optimization: B5

## Critical Dependencies for Next Roles
- DatabaseArchitect: encode zone metadata, pricing-rule integrity, and order polymorphism constraints.
- APIArchitect: enforce secure boundary and contract-first route catalog.
- IntegrationArchitect: formalize partner sync/idempotency and provider fallback standards.
- FilamentArchitect: align admin state transitions with customer-visible lifecycle.

## Known Gaps in Wave 1 Inputs
- Protocol-listed source artifacts were not present at expected paths:
  - `docs/logistics/audit/fleetbase-audit.md`
  - `docs/logistics/audit/logistics-topic-scan.md`
  - `docs/logistics/audit/harshithva-analysis.md`
  - `docs/logistics/audit/norwegian-post-services-analysis.md`
- This catalog is synthesized from available audit/logistics documents and should be refreshed once missing role artifacts are produced.
