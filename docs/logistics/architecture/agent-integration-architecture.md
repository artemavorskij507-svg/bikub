# Bikube Logistics Agent Integration Architecture

Date: 2026-03-31  
Role: IntegrationArchitect  
Status: Proposed (Wave 2)

## 1) Scope And Compatibility
This architecture defines AgencyAgents integration for logistics operations with three primary workflows:
- Dispatcher workflow (intake, triage, assignment, re-assignment)
- Analyst workflow (risk/anomaly detection, prediction, recommendations)
- Support workflow (customer/ops incident triage and resolution)

Compatibility constraints:
- Laravel 11
- Filament v3
- Livewire 3
- Additive integration only; no breaking changes to existing Bikube modules

## 2) Integration Goals
- Provide a carrier-agnostic, agent-orchestrated logistics control plane.
- Keep existing Bikube order and delivery lifecycle intact while adding agent automation.
- Standardize events and contracts for deterministic retries and replay.
- Support graceful degradation to manual operations when agents or providers fail.

## 3) Agent Roles And Boundaries

### 3.1 Dispatcher Agent
Responsibilities:
- Accept dispatch intents from order/shipment events.
- Select candidate route, warehouse, and courier/personnel.
- Publish assignment decisions and assignment confidence.
- Re-run assignment on disruption signals.

Must not:
- Mutate financial totals.
- Finalize customer compensation outcomes (Support owns this).

### 3.2 Analyst Agent
Responsibilities:
- Consume telemetry and tracking streams.
- Detect SLA risk, ETA drift, capacity imbalance, route anomalies.
- Emit ranked recommendations (reroute, rebalance, manual intervention).
- Provide confidence score and explainability metadata.

Must not:
- Directly assign personnel without Dispatcher confirmation.

### 3.3 Support Agent
Responsibilities:
- Open and manage support incidents tied to shipment/order/timeline.
- Synthesize incident context from timeline + analyst outputs.
- Trigger customer notifications and internal escalation tasks.
- Close incidents with resolution code and audit trail.

Must not:
- Override shipment state transitions without authorized workflow command.

## 4) System Context
- Producers:
  - Order/Shipment services
  - Routing service
  - Tracking ingestion
  - Payment and cancellation handlers
- Consumer/Orchestrator:
  - `AgencyAgentOrchestrator` (application service)
- Agent adapters:
  - `DispatcherAdapter`
  - `AnalystAdapter`
  - `SupportAdapter`
- Persistence:
  - `shipments`, `tracking_events`, `routes`, `route_stops`, `personnel`, `agent_tasks`, `agent_task_attempts`, `incident_cases`
- Transport:
  - Queue topics/jobs + webhook callbacks where needed

## 5) Canonical Workflows

### 5.1 Dispatcher Workflow
1. Trigger: `shipment.created` or `dispatch.replan.requested`.
2. Orchestrator creates `agent_task` (`kind=dispatcher.assign`).
3. Dispatcher adapter receives normalized dispatch request contract.
4. Dispatcher returns ranked assignment candidates.
5. Orchestrator validates constraints (capacity, zone, status, lock version).
6. On success: persist assignment and emit `dispatch.assigned`.
7. On low confidence/conflict: emit `dispatch.manual_review_required`.

### 5.2 Analyst Workflow
1. Trigger: `tracking.event.recorded`, `eta.recomputed`, or periodic schedule.
2. Orchestrator creates `agent_task` (`kind=analyst.evaluate`).
3. Analyst adapter evaluates trend/risk and returns findings.
4. Orchestrator stores `analysis_result` with confidence and features.
5. If threshold exceeded: emit `analyst.risk_detected`.
6. Optional follow-up command: `dispatch.replan.requested` or `support.case.requested`.

### 5.3 Support Workflow
1. Trigger: `analyst.risk_detected`, `delivery.exception.reported`, customer complaint, or failed delivery.
2. Orchestrator creates `agent_task` (`kind=support.triage`).
3. Support adapter composes incident dossier and recommended actions.
4. Orchestrator opens/updates `incident_case`.
5. If customer-impacting: emit `support.customer_notification_requested`.
6. On resolution: emit `support.case_resolved`.

## 6) Event Architecture

### 6.1 Event Envelope (Required Fields)
All domain events must include:
- `event_id` (UUID)
- `event_type` (string)
- `schema_version` (int)
- `occurred_at` (UTC ISO8601)
- `tenant_id` (nullable, for multi-tenant)
- `correlation_id`
- `causation_id`
- `entity_type` (`order|shipment|incident|route`)
- `entity_id`
- `producer`
- `idempotency_key`
- `payload` (JSON object)

### 6.2 Core Event Catalog
- `shipment.created`
- `shipment.status.changed`
- `tracking.event.recorded`
- `eta.recomputed`
- `dispatch.assignment.requested`
- `dispatch.assigned`
- `dispatch.replan.requested`
- `dispatch.manual_review_required`
- `analyst.risk_detected`
- `analyst.recommendation.published`
- `support.case.requested`
- `support.case.opened`
- `support.customer_notification_requested`
- `support.case_resolved`
- `integration.provider.degraded`
- `integration.provider.recovered`

### 6.3 Delivery Guarantees
- At-least-once delivery for all async events.
- Consumer idempotency required via `event_id` + `idempotency_key`.
- Ordered processing guaranteed only per entity partition key (`shipment_id`).

## 7) Contracts

### 7.1 Dispatcher Request Contract (`dispatcher.assign.v1`)
```json
{
  "request_id": "uuid",
  "shipment_id": 12345,
  "service_type": "delivery",
  "priority": "normal",
  "pickup": {"lat": 68.43, "lng": 17.42, "zone_code": "narvik-city"},
  "dropoff": {"lat": 68.44, "lng": 17.37, "zone_code": "narvik-city"},
  "constraints": {"max_eta_min": 45, "vehicle_type": "car"},
  "candidates": [{"personnel_id": 201, "capacity_free": 7}],
  "context": {"route_provider": "osrm", "weather": "snow"}
}
```

Dispatcher response (`dispatcher.assign.result.v1`):
```json
{
  "request_id": "uuid",
  "decision": "assign",
  "confidence": 0.84,
  "selected_personnel_id": 201,
  "selected_route_id": 9901,
  "alternatives": [{"personnel_id": 202, "score": 0.73}],
  "explanations": ["closest_available", "sla_safe"],
  "requires_manual_review": false
}
```

### 7.2 Analyst Contract (`analyst.evaluate.v1`)
Request includes shipment timeline slice, latest ETA, telemetry aggregates, and SLA target.  
Response includes:
- `risk_level` (`low|medium|high|critical`)
- `risk_score` (`0..1`)
- `signals` (array)
- `recommended_actions` (array)
- `confidence` (`0..1`)

### 7.3 Support Contract (`support.triage.v1`)
Request includes incident trigger, shipment summary, customer profile flags, and last known timeline anomalies.  
Response includes:
- `case_priority` (`p1|p2|p3|p4`)
- `customer_message_template_id`
- `internal_actions`
- `escalation_required` (bool)
- `resolution_sla_minutes`

### 7.4 Command Endpoints (Internal)
- `POST /internal/logistics/agent/dispatch/assign`
- `POST /internal/logistics/agent/analyst/evaluate`
- `POST /internal/logistics/agent/support/triage`
- `POST /internal/logistics/agent/support/resolve`

All endpoints:
- Require service authentication (`auth:sanctum` + scoped token/policy).
- Require `X-Idempotency-Key`.
- Return deterministic error codes (`409` conflict, `422` contract invalid, `503` provider unavailable).

## 8) Failure Handling And Recovery

### 8.1 Failure Classes
- Contract failure: invalid payload/schema mismatch.
- Transient infrastructure failure: timeout, queue congestion, provider 5xx.
- Business conflict: stale assignment version, no eligible personnel, zone restriction.
- Permanent provider failure: bad credentials, revoked access, unsupported operation.

### 8.2 Handling Policies
- Retries:
  - Exponential backoff (`30s`, `2m`, `10m`, max 5 attempts).
  - Retry only transient classes.
- Dead-letter queue:
  - Move after max attempts.
  - Auto-create `support.case.requested` for critical task kinds.
- Circuit breaker:
  - Open after threshold (for example 5 failures / 2 minutes).
  - While open, route to fallback/manual workflow and emit `integration.provider.degraded`.
- Reconciliation:
  - Scheduled job compares expected state vs latest timeline.
  - Emit compensating commands (`dispatch.replan.requested`, `support.case.requested`) when divergence detected.

### 8.3 Human-In-The-Loop Fallback
- Manual review queue in Filament for:
  - Low-confidence dispatcher decisions
  - Critical analyst alerts
  - Dead-lettered support triage
- Every manual decision emits corresponding domain event with actor metadata.

## 9) Data Model Additions (Integration Layer)
- `agent_tasks`
  - `id`, `task_kind`, `entity_type`, `entity_id`, `status`, `attempt_count`, `provider`, `correlation_id`, `payload`, `result`, `last_error`, timestamps
- `agent_task_attempts`
  - `id`, `agent_task_id`, `attempt_no`, `started_at`, `finished_at`, `outcome`, `error_code`, `error_detail`
- `incident_cases`
  - `id`, `shipment_id`, `status`, `priority`, `opened_by`, `assigned_to`, `summary`, `resolution_code`, `resolved_at`
- `event_dedup`
  - `idempotency_key`, `event_id`, `consumer`, `processed_at`

## 10) Security, Compliance, And Auditability
- Enforce least-privilege service tokens per agent adapter.
- Encrypt sensitive payload fields at rest where personal data appears.
- Persist full decision trail (input hash, output hash, confidence, actor/provider, timestamps).
- Keep GDPR erasure/export compatibility by linking agent artifacts to canonical entity IDs.
- Prevent secret leakage in logs; redact tokens and PII fields in all integration logs.

## 11) Observability And SLOs
- Required metrics:
  - `agent_task_latency_ms` (by `task_kind`, `provider`)
  - `agent_task_success_rate`
  - `agent_task_retry_count`
  - `manual_review_rate`
  - `incident_open_to_resolve_minutes`
  - `dispatch_replan_frequency`
- Alerts:
  - High DLQ growth
  - Circuit breaker open duration
  - Spike in `dispatch.manual_review_required`
  - Support P1 backlog threshold breach

## 12) Rollout Plan
1. Phase 1: Event envelope + `agent_tasks` infrastructure (no auto-actions).
2. Phase 2: Dispatcher workflow with manual approval gate.
3. Phase 3: Analyst recommendations with threshold-based escalations.
4. Phase 4: Support triage automation + customer notification integration.
5. Phase 5: Enable closed-loop replan on analyst risk with strict guardrails.

## 13) Handoff Dependencies
- `APIArchitect`: align internal endpoint contracts and error model.
- `MigrationDeveloper`: create additive integration tables and indexes.
- `ModelDeveloper`: define Eloquent models/relations for agent tasks and incidents.
- `ControllerDeveloper`: implement internal agent command controllers and policy checks.
- `TestWriter`: add contract tests, idempotency tests, retry/DLQ behavior tests.
