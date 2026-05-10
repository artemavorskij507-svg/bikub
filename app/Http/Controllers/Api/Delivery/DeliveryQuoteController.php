<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Delivery\DeliveryQuoteRequest;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Services\Delivery\GeofenceService;
use App\Services\Delivery\TariffCalculator;
use Illuminate\Http\JsonResponse;

class DeliveryQuoteController extends Controller
{
    public function __construct(
        protected TariffCalculator $tariffCalculator,
        protected GeofenceService $geofenceService,
    ) {}

    public function __invoke(DeliveryQuoteRequest $request): JsonResponse
    {
        $data = $request->validatedPayload();
        $type = $data['type'];

        $pickupLocation = $data['pickup_location'] ?? [];
        $deliveryLocation = $data['delivery_location'] ?? [];

        $storeInfo = null;
        $restaurantInfo = null;

        if ($type === 'grocery') {
            $store = RetailStore::query()
                ->active()
                ->whereKey($data['store_id'])
                ->firstOrFail();

            $pickupLocation = $pickupLocation ?: [
                'lat' => (float) $store->latitude,
                'lng' => (float) $store->longitude,
                'address' => $store->address,
            ];

            $storeInfo = [
                'id' => $store->id,
                'name' => $store->name,
                'brand' => $store->brand ?? $store->chain_name,
                'address' => $store->address,
                'city' => $store->city,
            ];
        }

        if ($type === 'food') {
            $restaurant = Restaurant::query()
                ->active()
                ->whereKey($data['restaurant_id'])
                ->firstOrFail();

            $pickupLocation = $pickupLocation ?: [
                'lat' => (float) $restaurant->latitude,
                'lng' => (float) $restaurant->longitude,
                'address' => $restaurant->address,
            ];

            $restaurantInfo = [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'brand' => $restaurant->brand,
                'address' => $restaurant->address,
                'city' => $restaurant->city,
            ];
        }

        if (! $pickupLocation || ! isset($pickupLocation['lat'], $pickupLocation['lng'])) {
            return $this->errorResponse('Pickup location coordinates are required for quote.');
        }

        $deliveryLocation = $this->ensureDeliveryLocation($deliveryLocation, $data['delivery_address'] ?? null);
        if (! $deliveryLocation || ! isset($deliveryLocation['lat'], $deliveryLocation['lng'])) {
            return $this->errorResponse('Delivery location coordinates are required for quote.');
        }

        $payload = array_merge($data, [
            'pickup_location' => $pickupLocation,
            'delivery_location' => $deliveryLocation,
        ]);

        $quote = $this->tariffCalculator->calculateQuote($type, $payload);
        $route = $quote['route'] ?? $this->geofenceService->buildRouteEstimate(
            $pickupLocation,
            $deliveryLocation,
            $type
        );

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $quote['type'] ?? $type,
                'currency' => $quote['currency'] ?? 'NOK',
                'breakdown' => $quote['breakdown'] ?? [],
                'total' => $quote['total'] ?? null,
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
        ]);
    }

    protected function ensureDeliveryLocation(?array $location, ?string $address): array
    {
        if ($location && isset($location['lat'], $location['lng'])) {
            return $location;
        }

        return $this->parseAddressToLocation($address);
    }

    protected function parseAddressToLocation(?string $address): array
    {
        // TODO: integrate real geocoding
        return [
            'address' => $address ?? 'Narvik',
            'lat' => 68.4387,
            'lng' => 17.4273,
        ];
    }

    protected function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 422);
    }
}
