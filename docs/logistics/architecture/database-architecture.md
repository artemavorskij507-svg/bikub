# Bikube Logistics Database Architecture

Date: 2026-03-31  
Role: DatabaseArchitect  
Status: Proposed (Wave 1)

## 1) Scope And Compatibility
This design introduces logistics-first tables while staying compatible with current Bikube schema and runtime constraints (Laravel 11, Filament v3, Livewire 3).

Included domains:
- `shipments`
- `parcels`
- `tracking_events`
- `routes`
- `warehouses`
- `zones`
- `personnel`
- `addresses`
- Existing `service_types` and `pricing_rules` integration

Compatibility anchors in current Bikube DB:
- `orders`
- `delivery_orders`
- `users`
- `employees`
- `service_types`
- `pricing_rules`
- `geo_zones`

## 2) Design Principles
- Keep migrations additive-first; avoid breaking existing modules.
- Reuse existing master data tables (`service_types`, `pricing_rules`, `geo_zones`) instead of replacing them.
- Use `BIGINT` PK/FK, UTC timestamps, and JSON payloads for Postgres/MySQL parity.
- Prefer `VARCHAR + CHECK` over hard `ENUM` for safer evolution.
- Model append-only event history in `tracking_events`.

## 3) Core Entity Relationships
- One `shipment` has many `parcels`.
- One `shipment` has many `tracking_events`.
- One `route` has many `route_stops`.
- `shipments` reference optional active `route`, `zone`, and assigned `personnel`.
- `warehouses` and `zones` are many-to-many (`warehouse_zones`).
- `personnel` bridges `users`/`employees` to logistics operations.
- `shipments` connect to Bikube `service_types` and `pricing_rules`.

## 4) Table Specifications

### 4.1 `addresses`
Reusable physical address registry (pickup, dropoff, warehouse, stop).

Columns:
- `id` BIGINT PK
- `fingerprint` CHAR(64) NULL (deterministic hash for dedup)
- `address_kind` VARCHAR(32) NOT NULL (`pickup|delivery|warehouse|route_stop|billing`)
- `line1` VARCHAR(255) NOT NULL
- `line2` VARCHAR(255) NULL
- `city` VARCHAR(128) NOT NULL
- `region` VARCHAR(128) NULL
- `postal_code` VARCHAR(32) NULL
- `country_code` CHAR(2) NOT NULL
- `latitude` DECIMAL(10,8) NULL
- `longitude` DECIMAL(11,8) NULL
- `formatted_address` TEXT NULL
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `CHECK (country_code = UPPER(country_code))`
- `CHECK ((latitude IS NULL) = (longitude IS NULL))`
- `UNIQUE (fingerprint)` when populated

Indexes:
- `idx_addresses_country_city (country_code, city)`
- `idx_addresses_postal (country_code, postal_code)`
- `idx_addresses_lat_lng (latitude, longitude)`

### 4.2 `warehouses`

Columns:
- `id` BIGINT PK
- `code` VARCHAR(40) NOT NULL
- `name` VARCHAR(160) NOT NULL
- `warehouse_type` VARCHAR(32) NOT NULL (`hub|crossdock|microhub|storefront`)
- `address_id` BIGINT NOT NULL FK -> `addresses.id`
- `partner_id` BIGINT NULL FK -> `partners.id`
- `timezone` VARCHAR(64) NOT NULL
- `capacity_parcels` INT NULL
- `is_active` BOOLEAN NOT NULL DEFAULT TRUE
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (code)`
- `CHECK (capacity_parcels IS NULL OR capacity_parcels >= 0)`

Indexes:
- `idx_warehouses_type_active (warehouse_type, is_active)`
- `idx_warehouses_partner_active (partner_id, is_active)`
- `idx_warehouses_address (address_id)`

### 4.3 `logistics_zones`
Logistics zoning layer; optionally linked to existing `geo_zones`.

Columns:
- `id` BIGINT PK
- `geo_zone_id` BIGINT NULL FK -> `geo_zones.id`
- `code` VARCHAR(40) NOT NULL
- `name` VARCHAR(160) NOT NULL
- `zone_type` VARCHAR(32) NOT NULL (`service_area|restricted|priority|pickup_window`)
- `country_code` CHAR(2) NOT NULL
- `priority` INT NOT NULL DEFAULT 100
- `geometry` JSON NULL (GeoJSON payload)
- `bbox_min_lat` DECIMAL(10,8) NULL
- `bbox_min_lng` DECIMAL(11,8) NULL
- `bbox_max_lat` DECIMAL(10,8) NULL
- `bbox_max_lng` DECIMAL(11,8) NULL
- `is_active` BOOLEAN NOT NULL DEFAULT TRUE
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (code)`
- `UNIQUE (geo_zone_id)` for 1:1 optional mapping
- `CHECK (bbox_min_lat IS NULL OR bbox_min_lat <= bbox_max_lat)`
- `CHECK (bbox_min_lng IS NULL OR bbox_min_lng <= bbox_max_lng)`

Indexes:
- `idx_lz_active_type_priority (is_active, zone_type, priority)`
- `idx_lz_country_active (country_code, is_active)`
- `idx_lz_bbox (bbox_min_lat, bbox_min_lng, bbox_max_lat, bbox_max_lng)`

### 4.4 `warehouse_zones`

Columns:
- `id` BIGINT PK
- `warehouse_id` BIGINT NOT NULL FK -> `warehouses.id`
- `zone_id` BIGINT NOT NULL FK -> `logistics_zones.id`
- `coverage_type` VARCHAR(24) NOT NULL (`primary|secondary|pickup|dropoff`)
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (warehouse_id, zone_id, coverage_type)`

Indexes:
- `idx_wz_zone_coverage (zone_id, coverage_type)`

### 4.5 `personnel`
Operational role layer over existing `users` and `employees`.

Columns:
- `id` BIGINT PK
- `user_id` BIGINT NOT NULL FK -> `users.id`
- `employee_id` BIGINT NULL FK -> `employees.id`
- `home_warehouse_id` BIGINT NULL FK -> `warehouses.id`
- `current_zone_id` BIGINT NULL FK -> `logistics_zones.id`
- `role` VARCHAR(32) NOT NULL (`dispatcher|courier|sorter|warehouse_manager|route_planner`)
- `status` VARCHAR(24) NOT NULL DEFAULT `active`
- `vehicle_type` VARCHAR(40) NULL
- `vehicle_capacity_kg` DECIMAL(10,3) NULL
- `max_parcel_count` INT NULL
- `last_latitude` DECIMAL(10,8) NULL
- `last_longitude` DECIMAL(11,8) NULL
- `last_location_at` TIMESTAMP NULL
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (user_id)`
- `UNIQUE (employee_id)` when populated
- `CHECK ((last_latitude IS NULL) = (last_longitude IS NULL))`
- `CHECK (max_parcel_count IS NULL OR max_parcel_count >= 0)`

Indexes:
- `idx_personnel_role_status (role, status)`
- `idx_personnel_wh_status (home_warehouse_id, status)`
- `idx_personnel_zone_status (current_zone_id, status)`

### 4.6 `routes`

Columns:
- `id` BIGINT PK
- `route_code` VARCHAR(48) NOT NULL
- `service_type_id` BIGINT NOT NULL FK -> `service_types.id`
- `origin_warehouse_id` BIGINT NOT NULL FK -> `warehouses.id`
- `destination_warehouse_id` BIGINT NULL FK -> `warehouses.id`
- `driver_personnel_id` BIGINT NULL FK -> `personnel.id`
- `status` VARCHAR(24) NOT NULL (`planned|active|completed|cancelled`)
- `planned_start_at` TIMESTAMP NOT NULL
- `planned_end_at` TIMESTAMP NULL
- `actual_start_at` TIMESTAMP NULL
- `actual_end_at` TIMESTAMP NULL
- `estimated_distance_km` DECIMAL(10,3) NULL
- `estimated_duration_minutes` INT NULL
- `route_geometry` JSON NULL
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (route_code)`
- `CHECK (planned_end_at IS NULL OR planned_end_at >= planned_start_at)`
- `CHECK (estimated_duration_minutes IS NULL OR estimated_duration_minutes >= 0)`

Indexes:
- `idx_routes_status_start (status, planned_start_at)`
- `idx_routes_driver_status (driver_personnel_id, status)`
- `idx_routes_service_status (service_type_id, status)`

### 4.7 `route_stops`

Columns:
- `id` BIGINT PK
- `route_id` BIGINT NOT NULL FK -> `routes.id`
- `stop_sequence` INT NOT NULL
- `stop_type` VARCHAR(24) NOT NULL (`pickup|warehouse|delivery|return`)
- `address_id` BIGINT NOT NULL FK -> `addresses.id`
- `zone_id` BIGINT NULL FK -> `logistics_zones.id`
- `shipment_id` BIGINT NULL FK -> `shipments.id`
- `scheduled_arrival_at` TIMESTAMP NULL
- `actual_arrival_at` TIMESTAMP NULL
- `departure_at` TIMESTAMP NULL
- `status` VARCHAR(24) NOT NULL DEFAULT `pending`
- `payload` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (route_id, stop_sequence)`

Indexes:
- `idx_route_stops_route_status (route_id, status)`
- `idx_route_stops_address (address_id)`
- `idx_route_stops_zone (zone_id)`
- `idx_route_stops_sched (scheduled_arrival_at)`

### 4.8 `shipments`

Columns:
- `id` BIGINT PK
- `shipment_number` VARCHAR(64) NOT NULL
- `order_id` BIGINT NULL FK -> `orders.id`
- `delivery_order_id` BIGINT NULL FK -> `delivery_orders.id`
- `service_type_id` BIGINT NOT NULL FK -> `service_types.id`
- `pricing_rule_id` BIGINT NULL FK -> `pricing_rules.id`
- `sender_user_id` BIGINT NULL FK -> `users.id`
- `recipient_user_id` BIGINT NULL FK -> `users.id`
- `origin_address_id` BIGINT NOT NULL FK -> `addresses.id`
- `destination_address_id` BIGINT NOT NULL FK -> `addresses.id`
- `current_route_id` BIGINT NULL FK -> `routes.id`
- `current_zone_id` BIGINT NULL FK -> `logistics_zones.id`
- `assigned_personnel_id` BIGINT NULL FK -> `personnel.id`
- `status` VARCHAR(32) NOT NULL (`created|labelled|picked_up|in_transit|out_for_delivery|delivered|failed|returned|cancelled`)
- `priority` VARCHAR(16) NOT NULL DEFAULT `normal`
- `parcel_count` INT NOT NULL DEFAULT 1
- `total_weight_kg` DECIMAL(10,3) NULL
- `total_volume_m3` DECIMAL(12,6) NULL
- `declared_value` DECIMAL(12,2) NULL
- `currency` CHAR(3) NOT NULL DEFAULT `NOK`
- `promised_delivery_at` TIMESTAMP NULL
- `picked_up_at` TIMESTAMP NULL
- `delivered_at` TIMESTAMP NULL
- `cancelled_at` TIMESTAMP NULL
- `external_reference` VARCHAR(80) NULL
- `idempotency_key` VARCHAR(80) NULL
- `metadata` JSON NULL
- `created_at`, `updated_at`, `deleted_at` (soft delete)

Constraints:
- `UNIQUE (shipment_number)`
- `UNIQUE (idempotency_key)` when populated
- `CHECK (parcel_count >= 1)`
- `CHECK (currency = UPPER(currency))`
- `CHECK (total_weight_kg IS NULL OR total_weight_kg >= 0)`
- `CHECK (total_volume_m3 IS NULL OR total_volume_m3 >= 0)`

Indexes:
- `idx_shipments_status_promised (status, promised_delivery_at)`
- `idx_shipments_order (order_id)`
- `idx_shipments_delivery_order (delivery_order_id)`
- `idx_shipments_service_status (service_type_id, status)`
- `idx_shipments_personnel_status (assigned_personnel_id, status)`
- `idx_shipments_route_status (current_route_id, status)`
- `idx_shipments_sender_created (sender_user_id, created_at)`
- `idx_shipments_recipient_created (recipient_user_id, created_at)`

### 4.9 `parcels`

Columns:
- `id` BIGINT PK
- `shipment_id` BIGINT NOT NULL FK -> `shipments.id`
- `parent_parcel_id` BIGINT NULL FK -> `parcels.id`
- `parcel_number` VARCHAR(64) NOT NULL
- `barcode` VARCHAR(128) NOT NULL
- `status` VARCHAR(24) NOT NULL (`created|sorted|loaded|in_transit|out_for_delivery|delivered|returned|lost|damaged`)
- `weight_kg` DECIMAL(10,3) NOT NULL
- `length_cm` DECIMAL(8,2) NULL
- `width_cm` DECIMAL(8,2) NULL
- `height_cm` DECIMAL(8,2) NULL
- `volumetric_weight_kg` DECIMAL(10,3) NULL
- `is_fragile` BOOLEAN NOT NULL DEFAULT FALSE
- `requires_signature` BOOLEAN NOT NULL DEFAULT FALSE
- `current_warehouse_id` BIGINT NULL FK -> `warehouses.id`
- `current_zone_id` BIGINT NULL FK -> `logistics_zones.id`
- `metadata` JSON NULL
- `created_at`, `updated_at`

Constraints:
- `UNIQUE (parcel_number)`
- `UNIQUE (barcode)`
- `CHECK (weight_kg > 0)`
- `CHECK (length_cm IS NULL OR length_cm > 0)`
- `CHECK (width_cm IS NULL OR width_cm > 0)`
- `CHECK (height_cm IS NULL OR height_cm > 0)`

Indexes:
- `idx_parcels_shipment_status (shipment_id, status)`
- `idx_parcels_wh_status (current_warehouse_id, status)`
- `idx_parcels_zone_status (current_zone_id, status)`
- `idx_parcels_parent (parent_parcel_id)`

### 4.10 `tracking_events`
High-volume immutable event stream.

Columns:
- `id` BIGINT PK
- `shipment_id` BIGINT NOT NULL FK -> `shipments.id`
- `parcel_id` BIGINT NULL FK -> `parcels.id`
- `route_id` BIGINT NULL FK -> `routes.id`
- `warehouse_id` BIGINT NULL FK -> `warehouses.id`
- `personnel_id` BIGINT NULL FK -> `personnel.id`
- `address_id` BIGINT NULL FK -> `addresses.id`
- `event_type` VARCHAR(48) NOT NULL
- `event_status` VARCHAR(16) NOT NULL DEFAULT `success`
- `event_time` TIMESTAMP NOT NULL
- `source_system` VARCHAR(32) NOT NULL (`scanner|mobile_app|integration|api|manual`)
- `source_event_id` VARCHAR(96) NULL
- `latitude` DECIMAL(10,8) NULL
- `longitude` DECIMAL(11,8) NULL
- `payload` JSON NULL
- `created_at`

Constraints:
- `CHECK ((latitude IS NULL) = (longitude IS NULL))`
- `UNIQUE (source_system, source_event_id)` (idempotency when upstream supplies IDs)

Indexes:
- `idx_te_shipment_time (shipment_id, event_time)`
- `idx_te_parcel_time (parcel_id, event_time)`
- `idx_te_route_time (route_id, event_time)`
- `idx_te_wh_time (warehouse_id, event_time)`
- `idx_te_type_time (event_type, event_time)`
- `idx_te_source_time (source_system, event_time)`

Recommended scale option:
- Partition `tracking_events` by month on `event_time` once volume exceeds ~50M rows.

## 5) Existing Table Integration

### 5.1 `service_types` (existing)
Keep table as source of truth. Additive logistics fields (either columns or extension table):
- `logistics_enabled` BOOLEAN
- `default_priority` VARCHAR(16)
- `default_pickup_sla_minutes` INT
- `default_delivery_sla_minutes` INT

Alternative extension table:
- `logistics_service_type_settings(service_type_id PK/FK, settings JSON, updated_at)`

### 5.2 `pricing_rules` (existing)
Keep current generic pricing engine. Add logistics scoping via extension table:
- `logistics_pricing_scopes`
  - `id` PK
  - `pricing_rule_id` FK -> `pricing_rules.id`
  - `zone_id` FK -> `logistics_zones.id` NULL
  - `warehouse_id` FK -> `warehouses.id` NULL
  - `service_type_id` FK -> `service_types.id` NULL
  - `distance_from_km`, `distance_to_km`
  - `weight_from_kg`, `weight_to_kg`
  - `multiplier`, `fixed_surcharge`
  - `valid_from`, `valid_until`
  - `priority`, `is_active`, `metadata`

Indexes:
- `idx_lps_rule_active_priority (pricing_rule_id, is_active, priority)`
- `idx_lps_zone_active (zone_id, is_active)`
- `idx_lps_wh_active (warehouse_id, is_active)`
- `idx_lps_validity (valid_from, valid_until)`

## 6) PostgreSQL / MySQL Compatibility Notes
- IDs: use Laravel `bigIncrements`/`foreignId` (maps cleanly to both engines).
- JSON: use logical JSON fields; Postgres stores `jsonb`, MySQL stores `json`.
- Status fields: `VARCHAR + CHECK` for forward compatibility.
- Time: use UTC `TIMESTAMP(6)` where supported.
- Booleans: native `boolean` in Postgres, `tinyint(1)` in MySQL via framework abstraction.
- Spatial strategy:
  - Baseline: GeoJSON in JSON columns + bbox numeric columns (portable).
  - Optional engine-specific acceleration:
    - Postgres: PostGIS `geometry` + GiST index
    - MySQL 8: `geometry` + SPATIAL INDEX
- Upserts:
  - Postgres: `ON CONFLICT`
  - MySQL: `ON DUPLICATE KEY UPDATE`
  - Prefer Laravel `upsert()` abstraction in application layer.

## 7) Migration Safety Notes
- Create new logistics tables first with nullable optional FKs where needed.
- Do not drop or rename existing tables (`orders`, `delivery_orders`, `service_types`, `pricing_rules`, `geo_zones`) in initial rollout.
- Backfill in batches (by PK windows), not full-table transactions.
- Enforce NOT NULL and stricter CHECK constraints only after backfill validation.
- Add indexes after bulk backfill for lower migration impact on large tables.
- Online index guidance:
  - Postgres: use `CREATE INDEX CONCURRENTLY` (outside transaction).
  - MySQL 8 InnoDB: prefer online DDL (`ALGORITHM=INPLACE`, `LOCK=NONE`) when available.
- Foreign keys:
  - Add after data quality checks to avoid bulk-load failures.
  - Use `ON DELETE SET NULL` for optional relationships; `CASCADE` only for dependent child tables (`parcels`, `tracking_events` under `shipments` where acceptable).
- Rollout sequence:
  1. Deploy schema (no traffic switch).
  2. Backfill shipments/parcels from legacy delivery structures.
  3. Enable dual-write (legacy + logistics tables).
  4. Switch reads gradually with feature flags.
  5. Remove dual-write only after reconciliation.

## 8) Minimal Query Patterns Covered By Indexes
- Shipment board by status + SLA: `shipments(status, promised_delivery_at)`.
- Courier workload: `shipments(assigned_personnel_id, status)`.
- Tracking timeline: `tracking_events(shipment_id, event_time)`.
- Parcel scanning: `parcels(barcode)` unique lookup.
- Route execution queue: `routes(status, planned_start_at)`.
- Zone operations: `logistics_zones(is_active, zone_type, priority)`.

## 9) Implementation Notes For Next Wave
- Generate Laravel migrations in `database/migrations` with engine-safe guards (`Schema::hasTable`, `Schema::hasColumn`) only where legacy conflict is likely.
- Add Eloquent models under `app/Modules/Logistics/Models`.
- Add factories and seeders for load tests focused on `tracking_events`.
