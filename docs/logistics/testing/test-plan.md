# Logistics Testing Strategy

## Unit
- Models: scopes, casts, relations
- Requests: validation rules and edge cases
- Services: pricing/routing/tracking state transitions

## Feature
- API v1 endpoints auth and response contracts
- Role-based access for worker/customer endpoints
- Tracking lifecycle and idempotency behavior

## Browser (future)
- Live map render and marker updates
- Worker dashboard route assignment workflow
- Warehouse 2D visualization interactions

## Non-functional
- Queue jobs retries and dead-letter behavior
- Redis TTL behavior for live positions (30s)
- Rate-limiting and abuse protection
