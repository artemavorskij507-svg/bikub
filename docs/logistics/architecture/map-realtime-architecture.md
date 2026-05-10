# Bikube Logistics Map and Realtime Architecture

Date: 2026-03-31  
Role: MapArchitect  
Status: Proposed (Wave 2)

## 1) Scope And Compatibility
This design defines real-time map behavior for Bikube logistics with:
- Laravel 11 event broadcasting via Reverb (preferred) and Pusher-compatible fallback.
- Redis-backed live position cache with strict 30-second TTL.
- Geozone enter/exit detection from streaming positions.
- Scalable marker clustering strategy for dispatcher and customer map views.
- Fallback polling when websocket transport is degraded or unavailable.

Compatibility constraints:
- Must fit Bikube runtime (Laravel 11, Filament v3, Livewire 3).
- Must remain additive and non-breaking for existing modules.

## 2) High-Level Architecture
1. Position Producers:
- Driver app, courier app, vehicle telematics push `lat/lng/speed/heading` updates.

2. Ingestion API:
- `POST /api/logistics/positions` writes canonical event rows and updates Redis live cache.

3. Realtime Broadcast Layer:
- Laravel events broadcast through Reverb.
- Clients subscribe using Echo.
- Pusher protocol compatibility for hosted fallback.

4. Geozone Engine:
- Streaming detector computes zone transitions (enter/exit) on each accepted position.
- Emits domain events and notification hooks.

5. Map Read APIs:
- Live marker feed, cluster feed, per-shipment route/status feed.
- Uses Redis first, DB/event log as fallback.

6. Frontend Map UI:
- Filament/Livewire pages and customer tracking views consume websocket stream.
- Switch to polling automatically on connection degradation.

## 3) Reverb and Pusher Event Channel Design

### 3.1 Channel Names
- `presence.logistics.dispatchers`
Purpose: dispatcher consoles and operations wallboards.

- `private.logistics.route.{routeId}`
Purpose: route-level live markers and stop progress.

- `private.logistics.shipment.{shipmentId}`
Purpose: single shipment tracking view.

- `private.logistics.driver.{personnelId}`
Purpose: individual driver stream and diagnostics.

- `private.logistics.zone.{zoneId}`
Purpose: zone-level operations feed.

### 3.2 Event Types
- `PositionUpdated`
Payload:
- `personnel_id`, `shipment_id` (nullable), `route_id` (nullable)
- `lat`, `lng`, `speed_kmh`, `heading_deg`, `accuracy_m`
- `recorded_at` (UTC ISO8601), `source`, `sequence`

- `GeozoneEntered`
Payload:
- `personnel_id`, `shipment_id` (nullable), `zone_id`, `zone_type`, `entered_at`

- `GeozoneExited`
Payload:
- `personnel_id`, `shipment_id` (nullable), `zone_id`, `zone_type`, `exited_at`

- `EtaUpdated`
Payload:
- `shipment_id`, `route_id`, `eta_at`, `delay_seconds`, `updated_at`

- `ConnectionHealth`
Payload:
- `region`, `channel`, `lag_ms_p50`, `lag_ms_p95`, `dropped_events`

### 3.3 Authorization Rules
- `presence.logistics.dispatchers`: only internal operations roles.
- `private.logistics.route.*`: assigned logistics staff and admins.
- `private.logistics.shipment.*`: owner customer + assigned staff + admins.
- `private.logistics.driver.*`: driver self, dispatchers, admins.
- `private.logistics.zone.*`: zone-managed teams + admins.

### 3.4 Delivery Guarantees
- Transport: at-most-once broadcast delivery.
- Application: idempotency via `sequence` and monotonic `recorded_at` per source.
- Client discards stale updates where `(source, sequence)` is older than local head.

## 4) Redis Live Position Cache (TTL 30 Seconds)

### 4.1 Key Schema
- `logistics:pos:personnel:{personnelId}` -> HASH, TTL 30s
Fields:
- `lat`, `lng`, `speed_kmh`, `heading_deg`, `accuracy_m`, `recorded_at`, `route_id`, `shipment_id`, `zone_set`

- `logistics:pos:shipment:{shipmentId}` -> HASH, TTL 30s
Fields:
- `lat`, `lng`, `recorded_at`, `route_id`, `personnel_id`

- `logistics:active:personnel` -> SET of personnel IDs currently fresh
Maintenance:
- add on write; cleanup job removes IDs with missing/expired `logistics:pos:personnel:*`

### 4.2 Write Path
1. Validate payload and auth.
2. Persist canonical position event in DB (`tracking_events` style append-only row).
3. Upsert Redis hashes and reset TTL to 30 seconds.
4. Broadcast `PositionUpdated`.
5. Run geozone transition check and broadcast enter/exit events.

### 4.3 Read Path
- Primary: fetch live marker state from Redis keys.
- Fallback: if key expired, pull latest DB event and mark marker `stale=true`.
- API always returns `freshness_seconds` so UI can gray stale markers.

### 4.4 TTL Semantics
- Exactly 30s TTL for live position keys.
- Marker states:
- `fresh` if last update <= 30s
- `stale` if > 30s and <= 5m
- `offline` if > 5m

## 5) Geozone Enter/Exit Detection

### 5.1 Inputs
- Current position `(lat,lng,recorded_at)`.
- Candidate zones from `logistics_zones` using bbox prefilter.
- Previous zone membership from cache field `zone_set`.

### 5.2 Detection Algorithm
1. Bounding-box prefilter:
- Query active zones where point is inside `bbox_*` rectangle.

2. Precise geometry check:
- If polygon exists, run point-in-polygon (ray-casting or winding number).
- If only bbox exists, bbox result is authoritative until polygon is defined.

3. Membership diff:
- `entered = current_zone_ids - previous_zone_ids`
- `exited = previous_zone_ids - current_zone_ids`

4. Persist and emit:
- Write transition events to event log.
- Broadcast `GeozoneEntered`/`GeozoneExited`.
- Update cached `zone_set` with the new membership.

### 5.3 Debounce and Noise Control
- Ignore updates with `accuracy_m` above configurable threshold.
- Optional hysteresis margin for border jitter (e.g., 20-40m buffer).
- Minimum transition interval per zone/entity (e.g., 15s) to prevent flapping.

## 6) Marker Clustering Strategy

### 6.1 Client-Side Clustering (Default)
Use client clustering for normal operation windows (dispatcher map <= 20k active markers):
- Algorithm: Supercluster-style KDBush clustering by zoom level.
- Recompute cluster buckets on viewport/zoom changes.
- Incremental marker updates from websocket stream mutate cluster index.

### 6.2 Server-Assisted Clustering (High Scale)
For large fleets or low-powered clients:
- Endpoint returns pre-aggregated cells by zoom precision.
- Cell strategy: geohash/H3 bucket + marker counts + centroid.
- Payload includes:
- `cluster_id`, `count`, `centroid_lat`, `centroid_lng`, `status_mix`, `stale_count`

### 6.3 Cluster Expansion Contract
- Clicking a cluster requests children markers for that cluster ID and zoom.
- UI progressively drills from cluster -> subcluster -> marker.

## 7) Fallback Polling Strategy

### 7.1 Switch Conditions
Client switches from websocket to polling when any condition is met:
- Reverb/Pusher connection not established after N retries.
- Heartbeat timeout beyond threshold (e.g., 15s).
- Consecutive channel auth failures.
- Browser/network policy blocks websocket transport.

### 7.2 Poll Endpoints
- `GET /api/logistics/map/live?bbox=...&zoom=...&since=...`
Returns markers or clusters depending on zoom and server policy.

- `GET /api/logistics/shipments/{shipment}/track?since=...`
Returns per-shipment delta stream.

### 7.3 Poll Cadence
- Active viewport + focused tab: every 5s.
- Active viewport + background tab: every 15s.
- Low-power mode: every 30s.
- Backoff on 429/5xx: exponential up to 60s.

### 7.4 Consistency Rules
- Poll responses carry `server_time` and `cursor`.
- If websocket recovers, client merges by `sequence` and resumes stream mode.
- Duplicate suppression by entity ID + sequence.

## 8) Operational and Reliability Controls
- Queue broadcast jobs to isolate ingestion latency from websocket fan-out.
- Circuit breaker to disable noisy providers per region.
- Dead-letter queue for failed geozone transition jobs.
- Metrics:
- ingest rate, broadcast lag, stale marker ratio, geozone transition count, reconnect rate.

## 9) Security and Privacy
- Use private/presence channels with explicit authorization policies.
- Do not expose raw driver phone or PII in realtime payloads.
- Enforce per-tenant and per-role scoping on all map feeds.
- Position history retention policy separate from live cache.

## 10) Implementation Plan (Wave 2 to Wave 3 Handoff)
1. Provision Reverb and channel authorization rules.
2. Add position ingestion endpoint and Redis write-through cache (TTL 30s).
3. Add geozone detection service with bbox prefilter + polygon check.
4. Add map live endpoint with cluster mode toggle.
5. Add frontend connection manager (stream <-> polling failover).
6. Add dashboards for freshness and websocket health.

## 11) Open Decisions
- Final zone geometry engine library selection.
- H3 vs geohash for server-side clustering in production.
- Per-role map visibility constraints for partner organizations.

## 12) Handoff
- APIArchitect: finalize endpoint contracts, cursor model, and authorization scopes.
- IntegrationArchitect: align Reverb deployment and queue topology per environment.
- LivewireDeveloper/FrontendDeveloper: implement map connection manager, cluster rendering, and stale marker UX.
- TestWriter: add tests for geozone transitions, TTL freshness states, and websocket-to-polling failover.
