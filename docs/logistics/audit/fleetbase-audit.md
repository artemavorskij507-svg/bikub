# Fleetbase Audit for Bikube Logistics (FleetbaseAuditor)

Date: 2026-03-31
Scope: Architecture patterns from Fleetbase relevant to Bikube logistics module (orders, routing, realtime tracking, API/event/queue, fleet/driver management).

## 1) Snapshot Reviewed
- Fleetbase app shell: `fleetbase/fleetbase` (`889b945`, 2026-03-27)
- Core platform package: `fleetbase/core-api` (`ed6ee87`)
- Logistics package: `fleetbase/fleetops` (`4c46b3b`)
- Data client package: `fleetbase/fleetops-data` (`332dc42`) (frontend data adapter package, not DB migrations)

## 2) Concrete Architecture Findings

### A. Modular logistics engine split
- Fleetbase runs as a package-composed Laravel app: core platform concerns in `core-api`, logistics domain in `fleetops`.
- Pattern value for Bikube: isolate logistics domain module from shared auth/tenant/webhook/platform concerns.

### B. API surface pattern (public + internal)
- Public routes are explicit (`v1`), while internal ops API is generated through `fleetbaseRoutes()` route macro (`int/v1`).
- CRUD shape is standardized through custom REST registrar methods (`queryRecord`, `findRecord`, `createRecord`, `updateRecord`, `deleteRecord`) and shared controller/model traits.
- Pattern value for Bikube: keep internal admin API uniform while preserving explicit public contracts.

### C. Order lifecycle as aggregate + flow
- `Order` model encapsulates lifecycle methods (`dispatch`, `cancel`, `complete`, activity updates, route/ETA setup).
- Dispatch pipeline uses post-commit event dispatch, then listeners for notifications/webhooks.
- Flow abstraction (`Flow`, `Activity`, `Condition`, `Event`) with transport config states supports configurable workflows.
- Pattern value for Bikube: move status transitions to domain service/state graph, not controller conditionals.

### D. Routing, distance, ETA, pricing composition
- Route/ETA externalization through `OSRM` wrapper with caching and graceful failure.
- `OrderTracker` computes progress + ETA from route distance and activity fallback.
- Service quote pipeline combines internal rate cards + vendor quote hooks + matrix distance fallback.
- Pattern value for Bikube: route/ETA as strategy providers; quote engine should support layered fallbacks.

### E. Realtime location architecture
- Driver/vehicle track endpoints persist latest entity location and append `positions` records.
- Broadcast events fan out to org/API-credential/entity channels via SocketCluster broadcaster abstraction.
- Live map endpoints rely on cache-backed read model with observer-driven invalidation.
- Pattern value for Bikube: separate write model (`positions`) from read model (`live_*` cache/projections).

### F. Fleet/driver/vehicle management
- Core entities: `fleets`, `drivers`, `vehicles`, plus pivot assignment tables (`fleet_drivers`, `fleet_vehicles`).
- Assignment operations are explicit actions (`assignDriver`, `assignVehicle`, remove variants).
- Observer hooks maintain consistency on delete/update.
- Pattern value for Bikube: assignment changes should be explicit commands with audit/event emission.

### G. Event + queue + webhook platform
- Domain events (`OrderDispatched`, `OrderDriverAssigned`, `OrderReady`, etc.) mapped to listeners for notification/webhook.
- Core webhook stack persists API events and request logs, uses queued delivery jobs with retry semantics.
- Scheduler drives operational jobs (dispatch loops, distance-time refresh, quote purge).
- Pattern value for Bikube: all external side effects (webhooks/notifications/integrations) should be async + replayable.

### H. Data and index patterns (important for scale)
- `orders` table is multi-tenant and heavily indexed by company/status/driver/tracking/schedule timestamps.
- `positions` has append-only history and explicit index on (`subject_uuid`, `created_at`) for timeline queries.
- Tracking timeline uses `tracking_statuses` with indexes by tracking number and created time.
- Fleet tables and pivot assignments are UUID-keyed and indexed for fast tenant filtering.
- Later migrations add composite performance indexes for operational filters (company+status, company+created_at, etc.).
- Pattern value for Bikube: pre-plan composite indexes around dashboard and dispatch queries, not only FK indexes.

## 3) Portable Patterns for Bikube (Actionable)

| Priority | Pattern | Bikube Implementation (Laravel 11) |
|---|---|---|
| P0 | Domain order aggregate + transition policy | `LogisticsOrderService` with allowed transition map + transaction boundary + `afterCommit()` event dispatch |
| P0 | Public API explicit, internal API standardized | Keep `/api/v1/logistics/*` explicit; add internal admin route registrar for standard CRUD/query semantics |
| P0 | Append-only tracking event stream + current projection | `shipment_tracking_events` table + `shipment_tracking_snapshots` projection updated async |
| P0 | Idempotent webhooks/integration ingest | Enforce idempotency key (`provider + external_event_id`) and dedupe window before processing |
| P0 | Async side effects | Queue all webhooks/notifications/vendor API calls; keep request cycle DB-only |
| P1 | Realtime read model split | Write: positions/events; Read: cache/materialized live map model invalidated via model observers/events |
| P1 | Routing abstraction with fallback | `RoutingProviderInterface` (OSRM/Mapbox/Google adapters) + timeout + stale-cache fallback |
| P1 | Quote pipeline layering | `QuoteEngine`: contract tariff -> surcharge rules -> external quote override -> cached fallback |
| P1 | Fleet assignment commands | Explicit `AssignDriverToFleet`, `AssignVehicleToFleet`, `AssignOrderToDriver` commands with audit log |
| P2 | Configurable flow definitions | Persist order workflow templates (states/activities/conditions) to avoid hardcoded lifecycle logic |

## 4) Suggested Bikube Module Shape
- `Modules/Logistics/Domain`: aggregates, policies, value objects, events.
- `Modules/Logistics/Application`: command handlers, query services, orchestration.
- `Modules/Logistics/Infrastructure`: Eloquent repos, provider adapters (routing, telematics, carrier APIs), queue jobs, webhooks.
- `Modules/Logistics/Http`: public API controllers + internal Filament/admin controllers.
- `Modules/Logistics/Realtime`: broadcasting channels, projection updaters, live cache services.

## 5) Risks Observed in Fleetbase to Avoid in Bikube
- Telematics async integration inconsistency: service references non-existing job names (`SyncDevicesJob`, `TestConnectionJob`) while concrete jobs are named differently (`SyncTelematicDevicesJob`, `TestTelematicConnectionJob`).
- Type mismatch risk in job handlers (`ProviderRegistry` typehint vs actual `TelematicProviderRegistry`).
- Implication for Bikube: enforce integration contract tests and container-resolution tests for queued jobs before release.

## 6) Minimal Execution Plan for Bikube
1. Implement P0 patterns first (order aggregate, event stream, idempotent ingest, async side effects, API boundary).
2. Add P1 scaling patterns (realtime projection/cache, routing abstraction, quote layering, assignment commands).
3. Introduce P2 dynamic workflow only after baseline flows are stable in production metrics.

## 7) Source Anchors (reviewed)
- `fleetbase-modules/fleetops/server/src/routes.php`
- `fleetbase-modules/fleetops/server/src/Models/Order.php`
- `fleetbase-modules/fleetops/server/src/Support/OrderTracker.php`
- `fleetbase-modules/fleetops/server/src/Support/OSRM.php`
- `fleetbase-modules/fleetops/server/src/Providers/EventServiceProvider.php`
- `fleetbase-modules/fleetops/server/src/Providers/FleetOpsServiceProvider.php`
- `fleetbase-modules/fleetops/server/src/Http/Controllers/Api/v1/DriverController.php`
- `fleetbase-modules/fleetops/server/src/Http/Controllers/Api/v1/VehicleController.php`
- `fleetbase-modules/fleetops/server/src/Http/Controllers/Internal/v1/OrderController.php`
- `fleetbase-modules/fleetops/server/src/Http/Controllers/Internal/v1/LiveController.php`
- `fleetbase-modules/fleetops/server/src/Support/LiveCacheService.php`
- `fleetbase-modules/fleetops/server/src/Support/Telematics/TelematicService.php`
- `fleetbase-modules/fleetops/server/src/Jobs/SyncTelematicDevicesJob.php`
- `fleetbase-modules/fleetops/server/src/Jobs/TestTelematicConnectionJob.php`
- `fleetbase-modules/fleetops/server/migrations/2023_04_27_053456_create_orders_table.php`
- `fleetbase-modules/fleetops/server/migrations/2023_04_27_053456_create_positions_table.php`
- `fleetbase-modules/fleetops/server/migrations/2023_04_27_053456_create_tracking_statuses_table.php`
- `fleetbase-modules/fleetops/server/migrations/2025_11_01_103634_add_performance_indexes_to_fleetops_tables.php`
- `fleetbase-modules/fleetops/server/migrations/2025_12_16_000003_add_performance_indexes_to_fleetops_core_tables.php`
- `fleetbase-modules/fleetops/server/migrations/2025_12_16_000001_add_subject_created_at_index_to_positions.php`
- `fleetbase-modules/core-api/src/Expansions/Route.php`
- `fleetbase-modules/core-api/src/Routing/RESTRegistrar.php`
- `fleetbase-modules/core-api/src/Traits/HasApiControllerBehavior.php`
- `fleetbase-modules/core-api/src/Traits/HasApiModelBehavior.php`
- `fleetbase-modules/core-api/src/Webhook/WebhookCall.php`
- `fleetbase-modules/core-api/src/Webhook/CallWebhookJob.php`
- `fleetbase-modules/core-api/migrations/2023_04_25_094311_create_api_events_table.php`
- `fleetbase-modules/core-api/migrations/2023_04_25_094311_create_webhook_request_logs_table.php`
- `fleetbase-modules/core-api/migrations/2023_04_25_094311_create_webhook_endpoints_table.php`
