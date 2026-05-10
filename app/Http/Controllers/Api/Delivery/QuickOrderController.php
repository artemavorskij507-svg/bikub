<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Delivery\DeliveryQuoteRequest;
use App\Jobs\Delivery\ProcessOrder;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Services\Delivery\GeofenceService;
use App\Services\Delivery\OrderFactory;
use App\Services\Delivery\TariffCalculator;
use Illuminate\Http\JsonResponse;

class QuickOrderController extends Controller
{
    public function __construct(
        protected OrderFactory $orderFactory,
        protected TariffCalculator $tariffCalculator,
        protected GeofenceService $geofenceService,
    ) {}

    /**
     * Create a delivery order by reusing the quote workflow.
     */
    public function store(DeliveryQuoteRequest $request): JsonResponse
    {
        $data = $request->validatedPayload();
        $type = $data['type'];
        $user = $request->user();

        $pickupContext = $this->resolvePickupContext($type, $data);
        $pickupLocation = $pickupContext['location'];
        $storeInfo = $pickupContext['store'];
        $restaurantInfo = $pickupContext['restaurant'];

        $deliveryLocation = $this->ensureDeliveryLocation($data);
        $payload = array_merge($data, [
            'pickup_location' => $pickupLocation,
            'delivery_location' => $deliveryLocation,
        ]);

        $quote = $this->tariffCalculator->calculateQuote($type, $payload);
        $route = $this->geofenceService->buildRouteEstimate($pickupLocation, $deliveryLocation, $type);

        $deliveryOrder = $this->orderFactory->create(
            $type,
            $user,
            $payload,
            null,
            $quote,
            $route
        );

        ProcessOrder::dispatch($deliveryOrder)->onQueue('delivery');

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $deliveryOrder->order_id,
                'delivery_order_id' => $deliveryOrder->id,
                'type' => $deliveryOrder->type->value,
                'tracking_status' => $deliveryOrder->tracking_status->value,
                'eta' => $deliveryOrder->eta?->toIso8601String(),
                'pricing' => $quote,
                'price' => $quote['total'] ?? null,
                'route' => [
                    'distance_km' => $route['distance_km'] ?? null,
                    'duration_minutes' => $route['duration_minutes'] ?? null,
                    'eta' => isset($route['eta']) && $route['eta'] ? $route['eta']->toIso8601String() : null,
                    'geo_zone_id' => $route['geo_zone_id'] ?? null,
                    'geo_zone_name' => $route['geo_zone_name'] ?? null,
                ],
                'store' => $storeInfo,
                'restaurant' => $restaurantInfo,
            ],
        ], 201);
    }

    protected function resolvePickupContext(string $type, array $data): array
    {
        $location = $data['pickup_location'] ?? null;
        $store = null;
        $restaurant = null;

        if ($type === 'grocery') {
            $store = RetailStore::query()
                ->active()
                ->where('id', $data['store_id'] ?? null)
                ->firstOrFail();

            $location = $location ?: [
                'lat' => (float) $store->latitude,
                'lng' => (float) $store->longitude,
                'address' => $store->address,
            ];
        }

        if ($type === 'food') {
            $restaurant = Restaurant::query()
                ->active()
                ->where('id', $data['restaurant_id'] ?? null)
                ->firstOrFail();

            $location = $location ?: [
                'lat' => (float) $restaurant->latitude,
                'lng' => (float) $restaurant->longitude,
                'address' => $restaurant->address,
            ];
        }

        if (! $location) {
            $location = $this->parseAddressToLocation($data['pickup_address'] ?? null);
        }

        return [
            'location' => $location,
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'brand' => $store->brand ?? $store->chain_name,
                'address' => $store->address,
                'city' => $store->city,
            ] : null,
            'restaurant' => $restaurant ? [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'brand' => $restaurant->brand,
                'address' => $restaurant->address,
                'city' => $restaurant->city,
            ] : null,
        ];
    }

    protected function ensureDeliveryLocation(array $data): array
    {
        $location = $data['delivery_location'] ?? null;
        if ($location && isset($location['lat'], $location['lng'])) {
            return $location;
        }

        return $this->parseAddressToLocation($data['delivery_address'] ?? $data['address'] ?? null);
    }

    /**
     * Parse address to location coordinates (temporary fallback).
     */
    protected function parseAddressToLocation(?string $address): array
    {
        return [
            'address' => $address,
            'lat' => 68.4387,
            'lng' => 17.4273,
        ];
    }
}
