# HarshithvaAnalyst - Logistics Pattern Audit for Bikube

Date: 2026-03-31  
Role: HarshithvaAnalyst  
Source repo: https://github.com/harshithva/logistics (analyzed from local clone at `/home/keks/vscode/pw-temp/logistics`)

## Scope
Extract reusable patterns/algorithms from `harshithva/logistics` and map them into Bikube (Laravel 11 + Filament v3 + Livewire 3), while flagging unsafe legacy practices.

## Executive Summary
The repo is a Laravel 7 logistics CRM with a useful core domain shape: shipment aggregate + status history, quote with line items, payment-state derivation, vendor cost ledger, and time-window financial reporting. These are reusable as domain patterns, but not as direct code due to security, correctness, and maintainability risks.

Best reusable assets for Bikube:
1. Shipment lifecycle as append-only status history with "current status" projection.
2. Invoice/payment state machine (`PAID`/`PARTIAL`/`PENDING`) derived from transactional values.
3. Vendor payable balance model (job cost minus advances/payments).
4. Time-window reporting pattern (month/year/financial year views).
5. Quote header + line item structure with approval transitions.

## Repo Snapshot (Relevant)
- Stack baseline: Laravel 7 + Sanctum + Vuex (`composer.json:18-21`).
- Logistics domain tables: shipments, shipment_statuses, packages, payments, quote_lists, shipment_vendor_details (`database/migrations/2020_05_30_084309_create_shipments_table.php:16-68`, `database/migrations/2020_06_07_211728_create_shipment_statuses_table.php:16-29`, `database/migrations/2020_10_23_110356_create_shipment_vendor_details_table.php:16-28`).
- API shape concentrated in `routes/api.php:54-121`.

## Extracted Patterns and Algorithms

### 1) Shipment Lifecycle with Current-State Projection
Source:
- Status history table with timestamped entries (`database/migrations/2020_06_07_211728_create_shipment_statuses_table.php:16-29`).
- "Current status" relation via latest status row (`app/Shipment.php:40-45`).
- Status append endpoint (`app/Http/Controllers/ShipmentStatusController.php:39-69`).

Reusable algorithm:
- Maintain immutable status history rows.
- Compute current state as most recent status event.
- Use history endpoint for audit trail and customer tracking.

Bikube adaptation:
- Keep `shipment_status_events` append-only.
- Add explicit enum/state machine in domain layer (not raw strings).
- Materialize `current_status` for fast Filament tables.

### 2) Billing Responsibility Resolution (`bill_to` decision)
Source:
- Bill-to routing logic in shipment create/update (`app/Http/Controllers/ShipmentController.php:162-175`, `386-399`).

Reusable algorithm:
- Given `bill_to` = consignor/consignee, route payable account to sender/receiver deterministically.

Bikube adaptation:
- Convert to domain service `ResolveBillingParty` with strict enum + validation.
- Persist both `billing_party_type` and `billing_party_id`.

### 3) Deterministic Document Numbering
Source:
- Incremental shipment counter in settings and docket/freight number generation (`app/Http/Controllers/ShipmentController.php:206-215`, `database/migrations/2021_08_13_134957_create_settings_table.php:16-20`).

Reusable algorithm:
- Sequence counter -> formatted transport identifiers.

Bikube adaptation:
- Move to atomic DB sequence/locking transaction.
- Generate tenant/year-scoped references (e.g., `BK-2026-000123`).
- Never mutate counter outside transaction.

### 4) Aggregate Financial Status Derivation
Source:
- Payment state derivation logic (`app/Http/Helpers.php:42-114`).
- Balance algorithm with advance + TDS (`app/Http/Helpers.php:119-123`).
- Customer invoice aggregation (`app/Http/Resources/CustomerInvoice.php:18-29`).

Reusable algorithm:
- `total_paid = advance + payment_sum`.
- Payment state by threshold:
  - `>= total`: PAID
  - `<= 0`: PENDING
  - else: PARTIAL
- Outstanding = `charge_total - paid - advance`.

Bikube adaptation:
- Extract to pure service class with unit tests.
- Use money value objects/decimal casting.
- Support overpayment/credit-note cases explicitly.

### 5) Vendor Settlement / Payable Ledger
Source:
- Vendor job cost model (`database/migrations/2020_10_23_110356_create_shipment_vendor_details_table.php:18-24`).
- Derived vendor balance (`app/Http/Resources/ShipmentSingle.php:64-71`).

Reusable algorithm:
- Vendor balance = committed vendor total - (vendor advance + vendor payments).

Bikube adaptation:
- Split into ledger entries (`debit/credit`) instead of recomputing per read.
- Include vendor expenses and commission as typed lines.
- Add reconciliation endpoint for accounting exports.

### 6) Multi-window Reporting Strategy
Source:
- Package/report window filters this month, last month, FY windows (`app/Http/Controllers/PackageController.php:30-89`).
- Dashboard aggregations and FY computations (`app/Http/Controllers/CustomerController.php:266-337`).

Reusable algorithm:
- Build report from reusable date-window presets (month, FY, prior FY).
- Aggregate both revenue and collection metrics.

Bikube adaptation:
- Centralize date-window builder service.
- Use query scopes and grouped SQL, avoid per-row loops.
- Cache dashboard aggregates.

### 7) Quote as Header + Line Items with Status Transitions
Source:
- Quote create/update with line replacement (`app/Http/Controllers/QuoteController.php:41-75`, `112-146`).
- Approve/decline transitions (`app/Http/Controllers/QuoteController.php:180-194`).

Reusable algorithm:
- Parent quote record with many route/weight/ETA/rate lines.
- Transition states from draft to approved/declined.

Bikube adaptation:
- Keep itemized quote schema.
- Add transition guards (cannot approve expired/revoked quote).
- Version line items instead of hard delete+recreate.

### 8) Event-driven Customer Notifications (Concept Only)
Source:
- Shipment updated event + listener registration (`app/Providers/EventServiceProvider.php:20-27`).
- Triggered on shipment create/status update (`app/Http/Controllers/ShipmentController.php:269`, `app/Http/Controllers/ShipmentStatusController.php:65-67`).

Reusable pattern:
- Domain event dispatch on lifecycle changes.
- Listener fan-out to notification channels.

Bikube adaptation:
- Queue notification jobs, retries, dead-letter, idempotency key.
- Provider credentials from secrets manager only.

## High-Risk Anti-Patterns to Avoid Reusing

1. Hardcoded secrets and disabled TLS checks.
- WhatsApp bearer token in code (`app/Http/Helpers.php:145`).
- SMS auth key in controller (`app/Http/Controllers/ShipmentController.php:596`).
- `CURLOPT_SSL_VERIFYHOST/PEER = 0` (`app/Http/Helpers.php:141-142`, `app/Http/Controllers/ShipmentController.php:607-608`).

2. Security by obscurity links.
- Static secret URL segments for customer docs (`routes/web.php:35-37`, QR URL at `app/Http/Controllers/ShipmentController.php:638`, `662`, `683`).

3. Data integrity and correctness gaps.
- No transactions around multi-table shipment create/update (`app/Http/Controllers/ShipmentController.php:116-270`, `345-463`).
- Potential N+1 and loop-heavy aggregates (`app/Http/Controllers/CustomerController.php:55-65`, `289-294`).
- Inconsistent status string spellings (`Intrasit` typo at `app/Http/Controllers/ShipmentController.php:586`).
- Incorrect relation definition (`app/QuoteList.php:11-12`).

4. Legacy platform drift.
- Laravel 7 baseline (`composer.json:18`) is below Bikube target runtime.

5. Minimal automated test coverage.
- Only default example tests (`tests/Feature/ExampleTest.php:15-20`, `tests/Unit/ExampleTest.php:14-17`).

## Bikube Implementation Backlog (Prioritized)

### P0 (Implement now)
1. Shipment lifecycle module:
- `shipments`, `shipment_status_events`, `shipment_packages`, `shipment_charges`, `shipment_documents`.
- Domain transitions and event bus.

2. Billing + payment state engine:
- Shared service for balance and status derivation.
- Unit tests for edge cases (zero, partial, overpaid, tds/discount).

3. Vendor payable ledger:
- Replace ad-hoc arithmetic with normalized payable entries.

4. Secure notification framework:
- Queue + provider adapters + env-based secrets.

### P1 (Next wave)
1. Quote workflow with line-item versioning.
2. Dashboard/report window service and cached aggregates.
3. Customer tracking endpoint using public tracking token (signed URL), not static route secrets.

### P2 (Optimization)
1. Materialized stats tables for high-volume analytics.
2. Event sourcing read models for ops dashboards.
3. SLA breach predictor (based on delivery date + latest status age).

## Suggested Bikube Data Model Shape
- `logistics_shipments`
- `logistics_shipment_status_events`
- `logistics_shipment_packages`
- `logistics_shipment_charges`
- `logistics_shipment_payments`
- `logistics_vendor_assignments`
- `logistics_vendor_ledger_entries`
- `logistics_quotes`
- `logistics_quote_lines`

## Direct Handoff to PatternExtractor and DatabaseArchitect
1. Promote these as canonical reusable patterns: lifecycle-event history, payment-state derivation, vendor payable formula, date-window reporting, quote header+lines.
2. Exclude direct reuse of helper transport code, route secreting, and hardcoded provider tokens.
3. Build Bikube migrations/services from normalized model above with transaction boundaries and queued notifications.
