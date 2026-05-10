# Bikube Filament Logistics Architecture (v3 Operations Center)

Date: 2026-03-31  
Role: FilamentArchitect  
Status: Proposed (Wave 2)

## Role
- FilamentArchitect

## Inputs
- Coordination protocol: `docs/logistics/coordination/multi-agent-logistics-orchestration.md`
- Existing Filament assets:
  - `app/Filament/Resources/DeliveryOrderResource.php`
  - `app/Filament/Pages/DeliveryOperationsBoard.php`
  - `app/Filament/Widgets/*` (existing KPI/ops widget pool)
- Wave 1 data foundation:
  - `docs/logistics/architecture/database-architecture.md`
  - `docs/logistics/audit/norwegian-post-services-analysis.md`
- Runtime constraints:
  - Laravel 11
  - Filament v3
  - Livewire 3

## Findings
- Bikube already has delivery UI building blocks (`DeliveryOrderResource`, `DeliveryOperationsBoard`) but they are not yet a full logistics control tower.
- Existing Filament inventory is large and cross-domain; logistics IA needs strict grouping and role-based visibility to avoid operator overload.
- Wave 1 introduces logistics-first entities (`shipments`, `parcels`, `tracking_events`, `routes`, `warehouses`, `logistics_zones`, `carrier_rate_cards`) that are not represented as dedicated Filament resources yet.
- Current resources mix older Filament patterns and multilingual naming; Wave 2 should define a v3-first canonical logistics IA and incremental migration path.

## Decisions
- Build a dedicated **Logistics Operations Center** navigation subtree in Filament.
- Keep `DeliveryOrderResource` and `DeliveryOperationsBoard` as compatibility anchors, but center new operations around `ShipmentResource` and command pages.
- Use page-first workflows for dispatch/control-tower operations; use resource-first workflows for master data, auditability, and finance reconciliation.
- Standardize logistics permission scopes with explicit abilities (`logistics.view`, `logistics.dispatch`, `logistics.pricing.manage`, `logistics.integrations.manage`).

## Information Architecture

### 1) Navigation Groups (Filament v3)
1. `Logistics Operations`
2. `Logistics Planning`
3. `Logistics Master Data`
4. `Logistics Pricing & SLA`
5. `Logistics Integrations`
6. `Logistics Audit`

### 2) Operations Pages (high-frequency workflows)
1. `LogisticsCommandCenter` (`/admin/logistics/command-center`)
- Purpose: single-pane operations control (active shipments, SLA risk, exceptions, map context).
- Layout:
  - Top row: KPI widgets.
  - Middle row: incident/exception inbox + dispatch queue.
  - Right rail: carrier health + queue health.
  - Bottom: live shipment timeline table.

2. `DispatchConsole` (`/admin/logistics/dispatch`)
- Purpose: assign/reassign courier/personnel, reprioritize urgent shipments, trigger recalculation.
- Core actions: assign personnel, swap route stop order, force ETA recompute, escalate incident.

3. `CarrierControlTower` (`/admin/logistics/carriers`)
- Purpose: carrier API health, webhook failures, delayed event sync, retry actions.
- Core actions: replay sync jobs, disable carrier service, rotate credentials (policy-gated).

4. `RoutePlanningWorkbench` (`/admin/logistics/routes/workbench`)
- Purpose: route planning and balancing across warehouses/zones.
- Core actions: create route draft, optimize sequence, publish route.

5. `PickupWindowMonitor` (`/admin/logistics/pickup-windows`)
- Purpose: track pickup cutoffs and readiness windows by warehouse/carrier.
- Core actions: mark ready, flag late, notify warehouse manager.

6. `SlaRiskBoard` (`/admin/logistics/sla-risk`)
- Purpose: SLA-at-risk queue with triage filters and mitigation playbooks.
- Core actions: priority bump, customer-notification trigger, manager escalation.

### 3) Core Resources (record-centric workflows)

1. `ShipmentResource`
- Model: `shipments`
- Pages: `List`, `Create`, `View`, `Edit`
- Key tabs on `View`:
  - `Overview`
  - `Parcels`
  - `Tracking Timeline`
  - `Pricing`
  - `Notifications`
  - `Carrier Payloads` (raw JSON, read-only)
- Relation Managers:
  - `ParcelsRelationManager`
  - `TrackingEventsRelationManager`
  - `NotificationLogRelationManager`

2. `RouteResource`
- Model: `routes`
- Pages: `List`, `Create`, `View`, `Edit`
- Relation Managers:
  - `RouteStopsRelationManager`
  - `AssignedShipmentsRelationManager`

3. `WarehouseResource`
- Model: `warehouses`
- Pages: `List`, `Create`, `View`, `Edit`
- Relation Managers:
  - `WarehouseZonesRelationManager`
  - `PersonnelRelationManager`

4. `LogisticsZoneResource`
- Model: `logistics_zones`
- Pages: `List`, `Create`, `View`, `Edit`
- Relation Managers:
  - `WarehousesRelationManager`
  - `ShipmentsRelationManager`

5. `PersonnelResource` (logistics-scoped profile over users/employees)
- Model: `personnel`
- Pages: `List`, `Create`, `View`, `Edit`

6. `CarrierAccountResource`
- Model: `carrier_accounts`
- Pages: `List`, `Create`, `View`, `Edit`
- Secret fields masked and policy-protected.

7. `CarrierServiceResource`
- Model: `carrier_services`
- Pages: `List`, `Create`, `View`, `Edit`

8. `CarrierRateCardResource`
- Model: `carrier_rate_cards`
- Pages: `List`, `Create`, `View`, `Edit`
- Version-aware filters (`effective_from`, `effective_to`).

9. `TrackingEventResource` (read-only)
- Model: `tracking_events`
- Pages: `List`, `View`
- Purpose: forensic visibility and support triage.

10. `PickupPointCacheResource` (read-only)
- Model: `pickup_points_cache`
- Pages: `List`, `View`
- Purpose: debug service-point resolution and TTL freshness.

11. `NotificationLogResource` (read-only)
- Model: `notification_log`
- Pages: `List`, `View`
- Purpose: recipient comms auditability.

### 4) Widget Catalog (Filament widgets for logistics)

#### Command Center widgets
1. `LogisticsKpiOverviewWidget`
- Active shipments, at-risk count, failed deliveries, return ratio.

2. `CarrierHealthWidget`
- API error rate, webhook delay, last successful sync per carrier.

3. `SlaBreachTrendWidget`
- 24h/7d breach trend by service type and zone.

4. `DispatchBacklogWidget`
- Unassigned shipments, urgent queue size, age buckets.

5. `PickupWindowRiskWidget`
- Warehouses near cutoff, readiness failures.

6. `EventIngestionLagWidget`
- Event lag percentile, stuck sync jobs, retry queue depth.

#### Resource header widgets
1. `ShipmentCostBreakdownWidget`
2. `ShipmentEventTimelineCompactWidget`
3. `RouteUtilizationWidget`
4. `WarehouseLoadWidget`
5. `RateCardCoverageWidget`

## UX / Interaction Model

### Dispatcher flow
1. Open `LogisticsCommandCenter`.
2. Filter `at_risk + unassigned`.
3. Open shipment quick panel.
4. Assign personnel and route.
5. Trigger notification template.

### Support flow
1. Search shipment by tracking/order.
2. Open `ShipmentResource::view`.
3. Review `Tracking Timeline` and `NotificationLog`.
4. Apply exception action (`escalate`, `request redelivery`, `hold at pickup point`).

### Carrier ops flow
1. Open `CarrierControlTower`.
2. Check failed webhooks / stale polling jobs.
3. Trigger replay + inspect payload.
4. Confirm event ingestion restored.

## Filament v3 Implementation Layout

### Suggested file tree
- `app/Filament/Pages/Logistics/LogisticsCommandCenter.php`
- `app/Filament/Pages/Logistics/DispatchConsole.php`
- `app/Filament/Pages/Logistics/CarrierControlTower.php`
- `app/Filament/Pages/Logistics/RoutePlanningWorkbench.php`
- `app/Filament/Pages/Logistics/PickupWindowMonitor.php`
- `app/Filament/Pages/Logistics/SlaRiskBoard.php`
- `app/Filament/Resources/Logistics/ShipmentResource.php`
- `app/Filament/Resources/Logistics/RouteResource.php`
- `app/Filament/Resources/Logistics/WarehouseResource.php`
- `app/Filament/Resources/Logistics/LogisticsZoneResource.php`
- `app/Filament/Resources/Logistics/PersonnelResource.php`
- `app/Filament/Resources/Logistics/CarrierAccountResource.php`
- `app/Filament/Resources/Logistics/CarrierServiceResource.php`
- `app/Filament/Resources/Logistics/CarrierRateCardResource.php`
- `app/Filament/Resources/Logistics/TrackingEventResource.php`
- `app/Filament/Resources/Logistics/PickupPointCacheResource.php`
- `app/Filament/Resources/Logistics/NotificationLogResource.php`
- `app/Filament/Widgets/Logistics/*`

### Resource/Page registration strategy
- Use Filament v3 panel discovery for `App\Filament\Resources\Logistics` and `App\Filament\Pages\Logistics`.
- Keep legacy delivery pages/resources visible during transition with lower navigation priority.
- Migration sequencing:
  1. Introduce new resources/pages behind feature flag.
  2. Pilot with operations role.
  3. Move shortcuts from legacy `DeliveryOperationsBoard` to `LogisticsCommandCenter`.
  4. Deprecate old pages only after parity validation.

## Permissions and Policy Matrix

1. `logistics.viewer`
- Read access to pages/resources except integrations secrets.

2. `logistics.dispatcher`
- Shipment/route assignment, priority updates, ETA recalculation.

3. `logistics.manager`
- Full operations + pricing override approvals + SLA controls.

4. `logistics.integration_admin`
- Carrier credentials, webhook config, replay/reconciliation actions.

5. `logistics.finance`
- Rate cards, surcharge audits, reconciliation views.

## Risks
- Existing mixed Filament conventions may cause duplicate navigation or inconsistent UX if new IA is added without grouping rules.
- Heavy real-time widgets can degrade panel performance unless polling intervals and query scopes are constrained.
- Policy gaps can expose carrier credentials or raw payload PII.
- Parallel legacy/new logistics pages can create operator confusion without explicit deprecation banners and redirects.

## Handoff
- `IntegrationArchitect`:
  - Provide webhook replay and carrier health service contracts for `CarrierControlTower`.
- `APIArchitect`:
  - Align shipment/route/notification endpoints with page action requirements.
- `ModelDeveloper`:
  - Materialize models/relations for resources listed above.
- `WidgetDeveloper`:
  - Implement command-center widget set with polling budgets and caching.
- `DocumentationWriter`:
  - Prepare operations runbook for dispatcher and support personas.
