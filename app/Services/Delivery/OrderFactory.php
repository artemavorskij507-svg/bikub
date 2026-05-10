<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Models\Delivery\BulkyOrder;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\FoodOrder;
use App\Models\Delivery\GroceryOrder;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderFactory
{
    public function __construct(
        protected TariffCalculator $tariffCalculator,
        protected GeofenceService $geofenceService,
    ) {}

    /**
     * Create a delivery order with shared quote/route context.
     */
    public function create(
        string|DeliveryType $type,
        User $user,
        array $data,
        ?array $tariff = null,
        ?array $quote = null,
        ?array $route = null,
    ): DeliveryOrder {
        $deliveryType = $this->resolveDeliveryType($type);

        return DB::transaction(function () use ($deliveryType, $user, $data, $tariff, $quote, $route) {
            $pickupLocation = $data['pickup_location'] ?? null;
            $deliveryLocation = $data['delivery_location'] ?? null;

            $resolvedQuote = $this->ensureQuote($deliveryType, $data, $quote, $tariff);
            $resolvedRoute = $this->ensureRoute($deliveryType, $pickupLocation, $deliveryLocation, $route);

            $total = $resolvedQuote['total'] ?? ($tariff['total'] ?? 0);
            $orderMetadata = $this->buildOrderMetadata($deliveryType, $resolvedQuote, $resolvedRoute);

            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'priority' => $data['is_urgent'] ?? false ? 'urgent' : 'normal',
                'estimated_total' => isset($total) ? (int) round($total * 100) : null,
                'total_amount' => $total ?? 0,
                'currency' => 'NOK',
                'payment_status' => 'pending',
                'location' => [
                    'pickup' => $pickupLocation,
                    'delivery' => $deliveryLocation,
                ],
                'metadata' => $orderMetadata,
            ]);

            $orderable = match ($deliveryType) {
                DeliveryType::GROCERY => $this->createGroceryOrder($data),
                DeliveryType::BULKY => $this->createBulkyOrder($data),
                DeliveryType::FOOD => $this->createFoodOrder($data),
            };

            $deliveryOrder = DeliveryOrder::create([
                'order_id' => $order->id,
                'type' => $deliveryType,
                'pickup_location' => $pickupLocation,
                'delivery_location' => $deliveryLocation,
                'pickup_address' => $data['pickup_address'] ?? ($pickupLocation['address'] ?? null),
                'delivery_address' => $data['delivery_address'] ?? ($deliveryLocation['address'] ?? null),
                'estimated_distance_km' => $this->extractRouteValue('distance_km', $resolvedRoute, $resolvedQuote),
                'estimated_duration_minutes' => $this->extractRouteValue('duration_minutes', $resolvedRoute, $resolvedQuote),
                'eta' => $this->resolveEta($resolvedRoute, $resolvedQuote),
                'substitution_policy' => $this->resolveSubstitutionPolicy($data['substitution_policy'] ?? 'strict'),
                'is_urgent' => $data['is_urgent'] ?? false,
                'tracking_status' => DeliveryTrackingStatus::PENDING,
                'orderable_type' => get_class($orderable),
                'orderable_id' => $orderable->id,
                'metadata' => $this->buildDeliveryOrderMetadata($resolvedQuote, $resolvedRoute, $tariff),
                'tracking_token' => (string) Str::uuid(),
            ]);

            return $deliveryOrder->fresh();
        });
    }

    protected function ensureQuote(DeliveryType $type, array $data, ?array $quote, ?array $tariff): ?array
    {
        if ($quote !== null) {
            return $quote;
        }

        if (! isset($data['pickup_location'], $data['delivery_location'])) {
            return $tariff;
        }

        $pickup = $data['pickup_location'];
        $delivery = $data['delivery_location'];

        if (
            isset($pickup['lat'], $pickup['lng'], $delivery['lat'], $delivery['lng'])
        ) {
            return $this->tariffCalculator->calculateQuote(
                $type->value,
                $data
            );
        }

        return $tariff;
    }

    protected function ensureRoute(DeliveryType $type, ?array $pickup, ?array $delivery, ?array $route): ?array
    {
        if ($route !== null) {
            return $route;
        }

        if (
            isset($pickup['lat'], $pickup['lng'], $delivery['lat'], $delivery['lng'])
        ) {
            return $this->geofenceService->buildRouteEstimate($pickup, $delivery, $type);
        }

        return null;
    }

    protected function buildOrderMetadata(DeliveryType $type, ?array $quote, ?array $route): array
    {
        $metadata = [
            'delivery_type' => $type->value,
        ];

        $deliveryMeta = array_filter([
            'quote' => $quote,
            'route' => $this->formatRouteMetadata($route),
        ]);

        if ($deliveryMeta) {
            $metadata['delivery'] = $deliveryMeta;
        }

        return $metadata;
    }

    protected function buildDeliveryOrderMetadata(?array $quote, ?array $route, ?array $tariff): array
    {
        return array_filter([
            'quote' => $quote,
            'route' => $this->formatRouteMetadata($route),
            'legacy_tariff' => $tariff,
        ]);
    }

    protected function extractRouteValue(string $key, ?array $route, ?array $quote)
    {
        $routeValue = $route[$key] ?? null;
        if ($routeValue !== null) {
            return $routeValue;
        }

        return $quote['route'][$key] ?? null;
    }

    /**
     * Resolve delivery type from string or enum.
     */
    protected function resolveDeliveryType(string|DeliveryType $type): DeliveryType
    {
        if ($type instanceof DeliveryType) {
            return $type;
        }

        return match ($type) {
            'grocery' => DeliveryType::GROCERY,
            'bulky' => DeliveryType::BULKY,
            'food' => DeliveryType::FOOD,
            default => throw new \InvalidArgumentException("Unknown delivery type: {$type}"),
        };
    }

    /**
     * Resolve substitution policy from string or enum.
     */
    protected function resolveSubstitutionPolicy(string|SubstitutionPolicy $policy): SubstitutionPolicy
    {
        if ($policy instanceof SubstitutionPolicy) {
            return $policy;
        }

        return match ($policy) {
            'strict' => SubstitutionPolicy::STRICT,
            'ai' => SubstitutionPolicy::AI,
            'contact' => SubstitutionPolicy::CONTACT,
            default => SubstitutionPolicy::STRICT,
        };
    }

    /**
     * Create grocery order.
     */
    protected function createGroceryOrder(array $data): GroceryOrder
    {
        $storeId = $data['store_id'] ?? $this->resolveDefaultStoreIdForLocation($data['delivery_location'] ?? null);

        $groceryOrder = GroceryOrder::create([
            'substitution_policy' => $this->resolveSubstitutionPolicy($data['substitution_policy'] ?? 'strict'),
            'is_urgent' => $data['is_urgent'] ?? false,
            'store_id' => $storeId,
            'preferred_delivery_window' => $data['preferred_delivery_window'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $groceryOrder->items()->create([
                    'product_id' => $item['product_id'] ?? $item['id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1),
                    'substitution_policy' => isset($item['substitution_policy'])
                        ? $this->resolveSubstitutionPolicy($item['substitution_policy'])
                        : $groceryOrder->substitution_policy,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        }

        return $groceryOrder;
    }

    /**
     * Create bulky order.
     */
    protected function createBulkyOrder(array $data): BulkyOrder
    {
        return BulkyOrder::create([
            'dimensions' => $data['dimensions'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'services' => $data['services'] ?? [],
            'requires_assembly' => $data['requires_assembly'] ?? false,
            'requires_disassembly' => $data['requires_disassembly'] ?? false,
            'floor_number' => $data['floor_number'] ?? null,
            'elevator_available' => $data['elevator_available'] ?? false,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Create food order.
     */
    protected function createFoodOrder(array $data): FoodOrder
    {
        return FoodOrder::create([
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'items' => $data['items'] ?? $data['food_items'] ?? [],
            'special_instructions' => $data['special_instructions'] ?? null,
            'temperature_requirements' => $data['temperature_requirements'] ?? null,
            'allergen_info' => $data['allergen_info'] ?? null,
        ]);
    }

    protected function resolveDefaultStoreIdForLocation(?array $location): ?int
    {
        $store = \App\Models\RetailStore::query()
            ->where('is_active', true)
            ->where('category', 'grocery')
            ->orderBy('id')
            ->first();

        return $store?->id;
    }

    protected function resolveEta(?array $route, ?array $quote): ?Carbon
    {
        $eta = $route['eta'] ?? ($quote['route']['eta'] ?? null);

        if ($eta instanceof Carbon) {
            return $eta;
        }

        if (is_string($eta)) {
            return Carbon::parse($eta);
        }

        $minutes = $route['duration_minutes'] ?? ($quote['route']['duration_minutes'] ?? null);

        return $minutes ? now()->addMinutes((int) $minutes) : null;
    }

    protected function formatRouteMetadata(?array $route): ?array
    {
        if (! $route) {
            return null;
        }

        return [
            'distance_km' => $route['distance_km'] ?? null,
            'duration_minutes' => $route['duration_minutes'] ?? null,
            'eta' => isset($route['eta']) && $route['eta'] instanceof Carbon
                ? $route['eta']->toIso8601String()
                : ($route['eta'] ?? null),
            'geo_zone_id' => $route['geo_zone_id'] ?? null,
            'geo_zone_name' => $route['geo_zone_name'] ?? null,
        ];
    }
}
