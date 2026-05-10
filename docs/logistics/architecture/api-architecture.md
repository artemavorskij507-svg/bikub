# Bikube Logistics API Architecture

Date: 2026-03-31  
Role: APIArchitect  
Status: Proposed (Wave 2)

## 1) Scope And Compatibility
This API architecture defines Bikube logistics REST v1 contracts for three audiences:
- Customer API: customer-facing shipment lifecycle and tracking.
- Worker API: courier/dispatcher field operations.
- Internal API: service-to-service orchestration, carrier adapters, and operations control.

Compatibility constraints:
- Laravel 11 + Sanctum auth.
- Filament v3 and Livewire 3 backoffice compatibility.
- Additive rollout only; no breaking changes to existing Bikube modules.

## 2) API Style Guide
- Protocol: HTTPS only.
- Base path: `/api/v1`.
- Media type: `application/json`.
- Time format: ISO-8601 UTC (`YYYY-MM-DDTHH:mm:ssZ`).
- IDs: opaque string IDs in API, mapped from DB `BIGINT` internally.
- Tracing: every response includes `X-Request-Id`.
- Versioning: URI major version (`/v1`) + additive fields for minor evolution.

## 3) Audience Segmentation And Base Paths
- Customer: `/api/v1/customer/*`
- Worker: `/api/v1/worker/*`
- Internal: `/api/v1/internal/*`

This separation enforces policy boundaries and independent rate limits.

## 4) Authentication And Authorization
### 4.1 Authentication methods
- Customer API:
  - Bearer token via Sanctum personal access token (PAT) for mobile/web clients.
  - Optional first-party session auth for Bikube web app.
- Worker API:
  - Bearer token via Sanctum PAT with worker-scoped abilities.
  - Device binding recommended (`device_id` claim) for courier apps.
- Internal API:
  - Bearer service token (Sanctum token with `internal:*` abilities) or signed JWT from trusted gateway.
  - Source IP allowlist for critical mutation endpoints.

### 4.2 Token abilities (minimum)
- Customer: `shipment:read`, `shipment:create`, `shipment:cancel`, `quote:create`.
- Worker: `route:read`, `stop:update`, `location:write`, `shipment:event:write`.
- Internal: `dispatch:write`, `route:optimize`, `carrier:book`, `carrier:webhook:ingest`, `webhook:manage`.

### 4.3 Authorization rules
- Ownership policies enforced for customer resources (`shipment.customer_id == auth_user`).
- Worker can mutate only assigned routes/stops unless dispatcher role granted.
- Internal APIs denied for non-service principals.

## 5) Resource Model (v1)
Primary resources:
- `quotes`
- `shipments`
- `parcels`
- `tracking-events`
- `routes`
- `route-stops`
- `assignments`
- `webhook-subscriptions`

Cross-resource fields:
- `status` values are enum strings (no numeric magic values).
- `metadata` object reserved for non-contract extensions.

## 6) REST v1 Endpoints
## 6.1 Customer Endpoints
| Method | Path | Description | Idempotency Key Required |
|---|---|---|---|
| POST | `/api/v1/customer/quotes` | Create shipping quote | Yes |
| POST | `/api/v1/customer/shipments` | Create shipment order | Yes |
| GET | `/api/v1/customer/shipments` | List customer shipments | No |
| GET | `/api/v1/customer/shipments/{shipment_id}` | Get shipment details | No |
| POST | `/api/v1/customer/shipments/{shipment_id}/cancel` | Cancel shipment (state-gated) | Yes |
| GET | `/api/v1/customer/shipments/{shipment_id}/tracking-events` | Timeline for shipment | No |
| GET | `/api/v1/customer/pickup-points` | Available pickup points by area | No |

## 6.2 Worker Endpoints
| Method | Path | Description | Idempotency Key Required |
|---|---|---|---|
| GET | `/api/v1/worker/routes/active` | Get worker active route(s) | No |
| GET | `/api/v1/worker/routes/{route_id}/stops` | Get ordered stop list | No |
| POST | `/api/v1/worker/stops/{stop_id}/arrive` | Mark arrival at stop | Yes |
| POST | `/api/v1/worker/stops/{stop_id}/complete` | Mark stop complete with outcomes | Yes |
| POST | `/api/v1/worker/shipments/{shipment_id}/events` | Add operational event | Yes |
| PATCH | `/api/v1/worker/location` | Update live worker location | No |
| POST | `/api/v1/worker/incidents` | Report delivery incident | Yes |

## 6.3 Internal Endpoints
| Method | Path | Description | Idempotency Key Required |
|---|---|---|---|
| POST | `/api/v1/internal/shipments/{shipment_id}/assignments` | Assign shipment to route/worker | Yes |
| POST | `/api/v1/internal/routes/optimize` | Trigger route optimization job | Yes |
| POST | `/api/v1/internal/carriers/{carrier}/quotes` | Carrier quote adapter | Yes |
| POST | `/api/v1/internal/carriers/{carrier}/bookings` | Carrier booking adapter | Yes |
| POST | `/api/v1/internal/carriers/{carrier}/cancel` | Carrier cancel adapter | Yes |
| POST | `/api/v1/internal/carriers/{carrier}/webhook-events` | Carrier webhook ingest endpoint | No (dedupe by event_id) |
| GET | `/api/v1/internal/shipments/{shipment_id}/timeline` | Canonical normalized event timeline | No |
| POST | `/api/v1/internal/webhook-subscriptions` | Register outbound webhook target | Yes |
| GET | `/api/v1/internal/webhook-deliveries` | Inspect webhook delivery logs | No |

## 7) Standard Request/Response Envelope
### 7.1 Success envelope
```json
{
  "data": {},
  "meta": {
    "request_id": "req_01JQ0Q6H4A4W8VYVY8YF3N2D7M",
    "timestamp": "2026-03-31T10:45:20Z"
  }
}
```

### 7.2 Error model
```json
{
  "error": {
    "code": "SHIPMENT_INVALID_STATE",
    "message": "Shipment cannot be cancelled from status in_transit",
    "details": {
      "shipment_id": "shp_01JQ0Q2MTS9VY6F8A0XW3FKB12",
      "current_status": "in_transit",
      "allowed_statuses": ["pending", "confirmed"]
    },
    "retryable": false
  },
  "meta": {
    "request_id": "req_01JQ0Q6H4A4W8VYVY8YF3N2D7M",
    "timestamp": "2026-03-31T10:46:01Z"
  }
}
```

### 7.3 Error codes and HTTP mapping
| HTTP | Code | Meaning |
|---|---|---|
| 400 | `BAD_REQUEST` | malformed payload or unsupported parameter |
| 401 | `UNAUTHENTICATED` | missing/invalid credentials |
| 403 | `FORBIDDEN` | authenticated but insufficient scope/policy |
| 404 | `NOT_FOUND` | resource not visible or does not exist |
| 409 | `CONFLICT` | state/version conflict or duplicate business key |
| 422 | `VALIDATION_FAILED` | semantic validation errors |
| 429 | `RATE_LIMITED` | quota exceeded |
| 500 | `INTERNAL_ERROR` | unexpected server error |
| 503 | `UPSTREAM_UNAVAILABLE` | carrier/provider temporary failure |

## 8) Pagination Model
Default listing pagination uses cursor-based navigation.

Request query parameters:
- `page_size` (default 25, max 100)
- `cursor` (opaque token from previous page)
- `sort` (limited allowlist per endpoint, default `-created_at`)

Response `meta.pagination` shape:
```json
{
  "meta": {
    "pagination": {
      "page_size": 25,
      "next_cursor": "eyJpZCI6InNocF8wMUpRLi4uIn0",
      "has_more": true
    }
  }
}
```

For backoffice export endpoints only, offset pagination can be enabled (`page`, `per_page`) behind internal scope.

## 9) Idempotency Keys
### 9.1 Contract
- Header: `Idempotency-Key` (required for POST/PATCH mutation endpoints marked in endpoint tables).
- Key format: client-generated UUIDv4 or ULID, max 128 chars.
- Scope: unique per `(principal_id, method, route, key)`.
- Retention window: 24 hours minimum.

### 9.2 Behavior
- First accepted request persists response hash + status + body snapshot.
- Replay with same key and identical payload returns stored response (`Idempotency-Replayed: true`).
- Replay with same key and different payload returns `409 CONFLICT` with code `IDEMPOTENCY_KEY_REUSE_MISMATCH`.

### 9.3 Storage recommendation
- Table: `api_idempotency_keys`
- Indexed columns: `key`, `principal_id`, `method`, `route_fingerprint`, `expires_at`.

## 10) Webhook Delivery Model
## 10.1 Outbound webhooks (Bikube -> partner)
Event classes:
- `shipment.created`
- `shipment.status_changed`
- `shipment.cancelled`
- `route.updated`
- `delivery.proof_uploaded`

Delivery contract:
- HTTP POST JSON payload.
- Headers:
  - `X-Bikube-Event-Id`
  - `X-Bikube-Event-Type`
  - `X-Bikube-Delivered-At`
  - `X-Bikube-Signature` (HMAC-SHA256 over raw body)
- Expected response: `2xx` within 10 seconds.

Retry policy:
- Exponential backoff: 1m, 5m, 15m, 1h, 6h (max 5 retries).
- Non-retry on `410 Gone` (auto-disable subscription).
- Dead-letter queue after max retries.

## 10.2 Inbound carrier webhooks (partner -> Bikube)
- Endpoint: `/api/v1/internal/carriers/{carrier}/webhook-events`
- Required fields: `event_id`, `event_type`, `occurred_at`, `resource_id`, `payload`.
- Dedupe key: `(carrier, event_id)`.
- Out-of-order handling: append-only event store + projection by event time and precedence rules.

## 10.3 Webhook event payload example
```json
{
  "event_id": "evt_01JQ0QKQ2JQY61XW0XP20H6WQ3",
  "event_type": "shipment.status_changed",
  "occurred_at": "2026-03-31T10:50:00Z",
  "resource": {
    "type": "shipment",
    "id": "shp_01JQ0Q2MTS9VY6F8A0XW3FKB12"
  },
  "data": {
    "previous_status": "assigned",
    "current_status": "in_transit",
    "location": {
      "lat": 59.9139,
      "lng": 10.7522
    }
  }
}
```

## 11) Rate Limiting
Rate limits are applied per token and IP with route-group overrides.

| Audience | Baseline Limit | Burst | Notes |
|---|---:|---:|---|
| Customer | 120 req/min | 30 | Higher limits for tracking GET endpoints |
| Worker | 180 req/min | 60 | Location updates allowed at 1 request/10s per device |
| Internal | 600 req/min | 120 | Service tokens only; strict alerting on 429 spikes |
| Webhook Ingest | 300 req/min per carrier | 100 | Separate limiter by carrier key |

Rate limit headers:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`
- `Retry-After` (on 429)

## 12) Example Payloads
## 12.1 Create quote (Customer)
Request:
```http
POST /api/v1/customer/quotes
Authorization: Bearer <token>
Idempotency-Key: 9f5cf816-6057-4af9-96cf-475c04d17d99
Content-Type: application/json
```

```json
{
  "pickup_address": {
    "line1": "Karl Johans gate 1",
    "city": "Oslo",
    "postal_code": "0154",
    "country_code": "NO"
  },
  "dropoff_address": {
    "line1": "Dronning Eufemias gate 16",
    "city": "Oslo",
    "postal_code": "0191",
    "country_code": "NO"
  },
  "service_type": "same_day",
  "parcels": [
    { "weight_kg": 2.3, "length_cm": 35, "width_cm": 20, "height_cm": 12 }
  ]
}
```

Response:
```json
{
  "data": {
    "quote_id": "qte_01JQ0QNTDBW3NQ77ZQ49Y6MM2F",
    "currency": "NOK",
    "total_price": 149.0,
    "eta_minutes": 95,
    "expires_at": "2026-03-31T11:15:00Z"
  },
  "meta": {
    "request_id": "req_01JQ0QNV7W6P8B9E9X7PR4QFMP",
    "timestamp": "2026-03-31T10:55:00Z"
  }
}
```

## 12.2 Create shipment (Customer)
```json
{
  "quote_id": "qte_01JQ0QNTDBW3NQ77ZQ49Y6MM2F",
  "reference": "ORDER-90812",
  "recipient": {
    "name": "Nora Hansen",
    "phone": "+4790011122"
  },
  "pickup_window": {
    "start_at": "2026-03-31T12:00:00Z",
    "end_at": "2026-03-31T13:00:00Z"
  },
  "dropoff_window": {
    "start_at": "2026-03-31T13:00:00Z",
    "end_at": "2026-03-31T16:00:00Z"
  }
}
```

## 12.3 Worker stop completion
```json
{
  "stop_outcome": "delivered",
  "proof": {
    "photo_url": "https://cdn.bikube.no/proof/prf_01JQ0R4F.jpg",
    "signature_name": "Nora Hansen"
  },
  "notes": "Left at reception"
}
```

## 12.4 Internal route optimization trigger
```json
{
  "date": "2026-04-01",
  "warehouse_id": "wh_01JQ0R9D6BS2EW1AX7D8P42RVQ",
  "objective": "min_distance",
  "constraints": {
    "max_route_minutes": 480,
    "vehicle_types": ["bike", "van"],
    "respect_time_windows": true
  }
}
```

## 13) Security And Operational Controls
- Enforce TLS and HSTS; reject plaintext traffic.
- Validate signed webhook payloads before parsing business data.
- Redact PII in logs; do not log auth tokens or full payloads with personal fields.
- Use optimistic locking (`version` field) for critical state transitions.
- Emit audit events for all internal mutation endpoints.

## 14) Implementation Notes For Laravel 11
- Route files:
  - `routes/api_customer_v1.php`
  - `routes/api_worker_v1.php`
  - `routes/api_internal_v1.php`
- Middleware stack:
  - `auth:sanctum`
  - scope/ability middleware by audience
  - request-id injection
  - idempotency middleware for marked endpoints
  - rate limiter profiles per audience
- Validation: FormRequest classes + enum-backed status validation.
- Responses: shared API Resource + exception renderer for standard error model.

## 15) Open Questions
- Final decision on service-token format for internal APIs (Sanctum-only vs JWT via gateway).
- Whether customer shipment cancellation should support compensating fee logic at API level or service layer only.
- SLA for webhook retention and replay window in regulated markets.

## 16) Handoff
- `IntegrationArchitect`: finalize carrier adapter contracts and webhook signature rules per provider.
- `MapArchitect`: align route optimization request schema with map/provider abstraction.
- `FilamentArchitect`: map internal endpoints to admin actions and role permissions.
- `APIEndpointDeveloper`: implement route groups, middleware, request/response resources, and contract tests from this spec.
