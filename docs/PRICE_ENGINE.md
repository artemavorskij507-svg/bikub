## GLF Price Engine

### Architecture
- **PricingRules** (DB) hold normalized data (`type`, `value`, `applies_to`, `conditions`, `priority`, `meta`).
- **PriceEngine** pulls active rules (cached for `config('pricing.cache_ttl')`, default 60s) and applies them in priority order.
- **DemandService** maintains per-zone multipliers in Redis/Cache; refreshed every minute via `pricing:demand:refresh`.
- **OrderContext** is an immutable struct describing the estimation request (service type, zone, distance, weight, flags, etc.).
- **PriceEstimateResult** returns subtotal (before demand multiplier), total, currency, breakdown, and duration.
- **PriceEstimateLog** persists every API result to support analytics and demand heuristics.

### Rule Types
| Type | Behaviour |
| --- | --- |
| `base_fee` / `flat` | Adds a fixed amount. |
| `distance` | Multiplies `value` by `distance_km`. |
| `weight_surcharge` | Optional threshold (meta `min_weight_kg`) and per-kg flag (`meta.per_kg`). |
| `time_multiplier` / `percentage` | Applies percentage of current subtotal; `conditions.hours` restricts time window. |
| `service_specific` | Adds specialized fixed amount (used for category overrides). |
| `demand_multiplier` | Reserved; calculated by `DemandService` and appended automatically. |

### Demand Metrics
- Command `php artisan pricing:demand:refresh` aggregates `price_estimate_logs` for the last N minutes (default 5) and stores per-zone request-per-minute counters.
- Scheduler runs this command every minute (`app/Console/Kernel.php`).
- `DemandService::getMultiplier($zone)` maps RPM to multipliers:
  - >=60 rpm → 1.35
  - >=40 rpm → 1.20
  - >=20 rpm → 1.10
  - else 1.00

### API
`POST /api/v1/price/estimate`
```json
{
  "service_type": "errand",
  "zone": "Narvik sentrum",
  "distance_km": 4.2,
  "total_weight_kg": 1.2,
  "scheduled_at": "2025-11-21T17:45:00+01:00",
  "is_urgent": true,
  "items": [
    {"category": "pharmacy", "weight_kg": 0.2}
  ]
}
```

Response:
```json
{
  "id": "adf0…",
  "subtotal": 108.35,
  "total": 118.50,
  "currency": "NOK",
  "breakdown": [
    {"rule_id": 501, "rule_name": "Errand Base Fee", "type": "base_fee", "amount": 49},
    {"rule_id": 502, "rule_name": "Urgent Task", "type": "percentage", "amount": 12.25},
    {"rule_id": 503, "rule_name": "Pharmacy Handling Fee", "type": "flat", "amount": 39},
    {"rule_id": null, "rule_name": "Demand multiplier", "type": "demand_multiplier", "amount": 9.15}
  ],
  "duration_ms": 64
}
```

Enable/disable via `config/feature_flags.php` (`enable_dynamic_pricing` or `FF_ENABLE_DYNAMIC_PRICING` env).

