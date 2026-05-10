# Logistics API Documentation (v1)

Base prefix: `/api/v1/logistics`

## Public
- `GET /tracking/{trackingNumber}` - track shipment without auth (limited fields)

## Authenticated
- `GET /shipments`
- `POST /shipments`
- `GET /shipments/{shipment}`
- `POST /shipments/{shipment}/tracking-events`
- `GET /map/personnel-positions`
- `GET /customer/shipments`
- `GET /worker/shipments` (delivery personnel only)

## Notes
- Auth: `auth:sanctum`
- Worker guard uses `EnsureDeliveryPersonnel` middleware.
- Response format: JSON with `data` envelope for entities.
