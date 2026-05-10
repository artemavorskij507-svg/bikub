# Norwegian Post Services Analysis (Posten / Bring / Helthjem)

Date: 2026-03-31  
Role: NorwegianPostAnalyzer  
Target: Bikube logistics module (Laravel 11 + Filament v3 + Livewire 3)

## Role
- NorwegianPostAnalyzer

## Inputs
- Bring developer API catalog and docs (Booking, Shipping Guide, Pickup Point, Tracking, Event Cast, Pickup, Address):  
  https://developer.bring.com/no/api/  
  https://developer.bring.com/api/booking/  
  https://developer.bring.com/api/shipping-guide_2/  
  https://developer.bring.com/api/pickup-point/  
  https://developer.bring.com/api/tracking/  
  https://developer.bring.com/api/event-cast/  
  https://developer.bring.com/api/pickup/  
  https://developer.bring.com/api/address/
- Posten customer app and official 2026 price list:  
  https://www.posten.no/en/app  
  https://www.posten.no/en/price-list
- Bring parcel service page (customer flow + notifications):  
  https://www.bring.no/en/services/parcels/parcel
- Helthjem developer portal and guides:  
  https://developer.helthjem.no/  
  https://developer.helthjem.no/guide/integration-fundamentals/track-a-parcel  
  https://developer.helthjem.no/guide/integration-fundamentals/generate-a-label  
  https://developer.helthjem.no/guide/integration-guides/b2c-integration
- Helthjem business terms / product docs:  
  https://static.helthjem.no/web/filer/pdf/Operasjonelle-vilka%CC%8Ar-Helthjem_01.07.2024.pdf  
  https://helthjem.no/bedrift-informasjon/produktark/produktvilkar-helthjem-standard-og-ekspress

## Findings

### 1) Customer logistics flows
- Posten: app-centric flow (track, pickup handling, notifications, and self-service actions) with pickup point/locker support.
- Bring: clear B2C flow for parcels to pickup points, with recipient notifications in app/SMS/email, repeated pickup reminders, and return flow options (QR or label).
- Helthjem: checkout flow starts with coverage check, then service-point/home choice, then booking/label/tracking. Home-delivery gaps are handled by pickup-point fallback in terms/docs.

### 2) Worker/operations flows
- Bring:
  - Pickup can be ordered via Pickup API (`/pickup/api/create`).
  - Booking/label generation is tightly coupled to shipment booking.
  - Locker delivery constraints (including recipient mobile validation in some markets) are enforced in booking rules.
- Helthjem:
  - Operations require timely API/EDI package registration before pickup.
  - Pickup-window discipline is contractual (packages must be ready at agreed pickup window; changes need notice).
  - Label quality/scanability and structured handoff through sorting and distribution are explicit in terms.

### 3) Tracking + notifications
- Bring:
  - Tracking API provides shipment-event timelines.
  - Event Cast API adds push-style webhook callbacks; event groups include delivery-attempt, delay/deviation, ready-for-pickup, notification sent, returned, etc.
  - Known constraints: no webhook edit after creation; fixed retry/expiration behavior; no historical replay in Event Cast (full history via Tracking API).
- Helthjem:
  - Event Log returns tracking events plus shipment metadata.
  - Supports EN/NO/SE language output for events.
  - Can return multi-item/multi-reference event sets and occasional duplicates (clients must deduplicate/idempotently store).

### 4) Pricing capability and model
- Posten publishes explicit retail price tables (2026), including clear domestic tiers (example: Norgespakke small up to 5kg at 76 NOK online, large 10kg at 140 NOK online, etc.).
- Bring pricing in integrations is mainly API-driven (Shipping Guide for service availability, ETA windows, and calculated price outputs), plus contractual/service-specific rules in Booking.
- Helthjem uses customer-specific pricing through contractual appendices; public product docs show structured surcharges and product limits. Example (returns product doc): mailbox return from 39 NOK, pickup-point return from 44 NOK, with transport supplement and oversize surcharge logic.

### 5) API capability comparison (implementation-relevant)
- Bring API breadth is high:
  - Quote/checkout: Shipping Guide, Pickup Point, Address, Postal Code
  - Execution: Booking, Pickup, Shipment
  - Tracking/change: Tracking, Event Cast, Modify Delivery
  - Analytics/docs: Reports, Documents, Invoice
- Helthjem API breadth is focused but complete for merchant logistics:
  - Coverage check
  - Nearby service points
  - Booking
  - Label retrieval (PDF/PNG/SVG/ZPL)
  - Tracking/event log
- Posten direct public API posture is less integration-centric for merchants than Bring; most merchant-grade integration paths are on Bring side of the group.

## Decisions
- Bikube should implement a carrier-abstraction layer with Bring + Helthjem adapters first; treat Posten retail capabilities as UX benchmark and fallback network insight, not primary merchant API target.
- Event ingestion must be event-sourced + idempotent by design (Bring webhook + polling, Helthjem event log polling).
- Pricing must combine:
  1. live API quote (when available),
  2. contract tariff tables,
  3. surcharge rules,
  4. fallback static pricing cache.

## Risks
- API credential gating: both Bring and Helthjem require account/customer setup for production access.
- Contractual variability: Helthjem pricing and some Bring service behavior are contract-dependent; hardcoded assumptions will drift.
- Event consistency: asynchronous carrier events can arrive out of order or duplicated.
- Frontend compliance: some carrier terms impose branding/communication requirements in checkout/notification surfaces.
- Operational SLA risk: pickup-window and EDI timing breaches can generate fees and service failures.

## Implementable Features For Bikube

### P0 (core, directly buildable now)
- Unified `CarrierAdapter` interface:
  - `checkCoverage()`, `findPickupPoints()`, `quote()`, `book()`, `getLabel()`, `track()`, `cancelOrModify()`.
- Shipment state machine with normalized events:
  - Map carrier events into Bikube statuses (`registered`, `in_transit`, `attempted_delivery`, `ready_for_pickup`, `delivered`, `returned`, `deviation`).
- Event ingestion pipeline:
  - Bring Event Cast webhook endpoint + signature/header verification.
  - Scheduled polling fallback (Bring Tracking + Helthjem Event Log).
  - Idempotency key `(carrier, tracking_no, event_code, event_time)`.
- Notification orchestration:
  - Trigger by normalized states (push/SMS/email), including pickup reminders and failed-attempt resolution prompts.
- Quote/checkout orchestration:
  - Coverage check -> delivery-method options -> pickup-point/locker selection -> SLA + price display.

### P1 (high leverage)
- Pickup-point UX:
  - Nearest points by geo/address, opening-hours view, locker dimension constraints.
- Label service:
  - Multi-format support (PDF/PNG/SVG/ZPL), print profile templates by warehouse printer type.
- Rules engine for fallback delivery:
  - Home -> pickup point/locker fallback per carrier rules and recipient preference.
- Tariff model enhancements:
  - Weight vs volumetric weight, oversized handling fee, seasonal/transport surcharge, contract versioning.

### P2 (ops and finance maturity)
- Pickup planning console for operations:
  - Pickup windows, cutoff monitoring, EDI-before-pickup guardrail checks.
- Carrier finance reconciliation:
  - Compare booked quote vs invoice line items; detect surcharge leakage.
- Delivery quality analytics:
  - Attempted-delivery rate, first-attempt success, ready-for-pickup dwell time, return rate by carrier/product.

## Bikube Technical Blueprint (suggested)
- Tables:
  - `carrier_accounts`, `carrier_services`, `carrier_rate_cards`, `shipments`, `shipment_packages`, `shipment_labels`, `shipment_events`, `pickup_points_cache`, `notification_log`.
- API routes:
  - `POST /api/v1/logistics/quotes`
  - `POST /api/v1/logistics/shipments`
  - `GET /api/v1/logistics/shipments/{id}/tracking`
  - `POST /api/v1/logistics/webhooks/bring/event-cast`
  - `POST /api/v1/logistics/shipments/{id}/refresh-events`
- Jobs:
  - `SyncBringTrackingJob`, `SyncHelthjemEventsJob`, `RepriceShipmentJob`, `DispatchShipmentNotificationsJob`.
- Filament:
  - Shipment timeline panel, pickup-window risk widget, surcharge anomaly widget.

## Handoff
- Next roles should consume this as canonical Norway-carrier input for:
  - `PatternExtractor` (derive reusable patterns from event/price/fallback architecture),
  - `DatabaseArchitect` (materialize event-sourced schema),
  - `APIArchitect` (define carrier-agnostic endpoints + webhook contracts),
  - `IntegrationArchitect` (adapter boundaries and retry/idempotency policy).
- Required implementation note: keep all carrier-specific constants versioned and date-stamped (e.g., Posten 2026 tariff snapshot, Bring booking behavior changes dated 2026-01-26).
