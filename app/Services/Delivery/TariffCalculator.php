<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryType;
use App\Models\GeoZone;
use App\Models\PricingRule;
use App\Models\ServiceType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class TariffCalculator
{
    public function __construct(
        protected GeofenceService $geofenceService
    ) {}

    /**
     * Calculate price for bulky order (legacy float response).
     */
    public function calculateForBulky(array $dimensions, array $services, array $location): float
    {
        $result = $this->calculateBulkyPricing(
            $dimensions,
            $services,
            $location,
            $location
        );

        return $result['total'];
    }

    /**
     * Calculate volume from dimensions.
     */
    protected function calculateVolume(array $dimensions): float
    {
        if (! isset($dimensions['length'], $dimensions['width'], $dimensions['height'])) {
            return 0;
        }

        // Convert cm³ to m³
        return ($dimensions['length'] * $dimensions['width'] * $dimensions['height']) / 1000000;
    }

    /**
     * Get base rate for bulky order.
     */
    protected function getBaseRateForBulky(float $volume, ?GeoZone $geoZone): float
    {
        $roundedVolume = number_format($volume, 4, '.', '');
        $cacheKey = "bulky.rate.{$roundedVolume}.".($geoZone?->id ?? 'default');

        return Cache::remember($cacheKey, 3600, function () use ($volume, $geoZone) {
            $baseRate = null;

            if ($serviceTypeId = $this->resolveServiceTypeId(DeliveryType::BULKY->value)) {
                $rule = $this->getPricingRulesForServiceType($serviceTypeId)
                    ->first(fn (PricingRule $rule) => $this->ruleMatchesBulkyContext($rule, $volume, $geoZone));

                if ($rule) {
                    $baseRate = (float) $rule->base_price;
                }
            }

            if ($baseRate === null) {
                $bulkyConfig = config('delivery.types.'.DeliveryType::BULKY->value, []);
                $baseRate = $bulkyConfig['base_rate'] ?? 200;
                $baseRate += $volume * ($bulkyConfig['rate_per_m3'] ?? 50);
            }

            if ($geoZone && isset($geoZone->metadata['pricing_modifier'])) {
                $baseRate *= $geoZone->metadata['pricing_modifier'];
            }

            return $baseRate;
        });
    }

    /**
     * Calculate extras for services.
     */
    protected function calculateServiceExtras(array $services, ?GeoZone $geoZone): float
    {
        $extras = 0;

        $bulkyConfig = config('delivery.types.'.DeliveryType::BULKY->value, []);
        $servicePrices = $bulkyConfig['service_prices'] ?? [
            'assembly' => 100,
            'disassembly' => 80,
            'packaging' => 50,
            'wrapping' => 30,
        ];

        foreach ($services as $service) {
            $extras += $servicePrices[$service] ?? 0;
        }

        return $extras;
    }

    /**
     * Detailed bulky pricing breakdown.
     */
    protected function calculateBulkyPricing(
        array $dimensions,
        array $services,
        array $pickupLocation,
        array $deliveryLocation
    ): array {
        $volume = $this->calculateVolume($dimensions);

        $geoZone = $this->geofenceService->findGeoZone(
            $deliveryLocation['lat'] ?? 0,
            $deliveryLocation['lng'] ?? 0,
            DeliveryType::BULKY
        );

        $baseRate = $this->getBaseRateForBulky($volume, $geoZone);
        $serviceExtras = $this->calculateServiceExtras($services, $geoZone);

        $route = $this->geofenceService->buildRouteEstimate(
            $pickupLocation,
            $deliveryLocation,
            DeliveryType::BULKY
        );

        $distanceKm = $route['distance_km'] ?? 0;
        $eta = $route['eta'] ?? null;

        // Get delivery fee from PricingRule (DB) or config (fallback)
        // Note: isUrgent should be passed from request if needed
        $feeData = $this->getDeliveryFee(
            DeliveryType::BULKY,
            $geoZone,
            $distanceKm,
            $volume,
            false, // isUrgent
            $eta
        );

        $deliveryFee = $feeData['total'];
        $weatherFactor = $this->getWeatherCoefficient();

        $subtotal = $baseRate + $serviceExtras;
        $total = ($subtotal + $deliveryFee) * $weatherFactor;

        return [
            'base_rate' => round($baseRate, 2),
            'service_extras' => round($serviceExtras, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'weather_factor' => $weatherFactor,
            'volume_m3' => $volume,
            'total' => round($total, 2),
            'route' => $route,
            'geo_zone_id' => $route['geo_zone_id'] ?? $geoZone?->id,
            'fee_breakdown' => $feeData,
        ];
    }

    /**
     * Get weather coefficient.
     */
    protected function getWeatherCoefficient(): float
    {
        return Cache::remember('weather.coefficient', 3600, function () {
            // In real implementation, fetch from WeatherData model
            // For now, return default
            return config('delivery.weather.default_coefficient', 1.0);
        });
    }

    /**
     * Calculate price for grocery order.
     */
    public function calculateForGrocery(
        array $items,
        array $pickupLocation,
        array $deliveryLocation,
        ?GeoZone $zone = null
    ): array {
        // Calculate items subtotal
        $itemsSubtotal = 0;
        foreach ($items as $item) {
            $itemsSubtotal += ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
        }

        // Calculate distance
        $distanceKm = $this->geofenceService->calculateDistance($pickupLocation, $deliveryLocation);

        // Calculate ETA
        $eta = $this->geofenceService->estimateDeliveryTime(
            $pickupLocation,
            $deliveryLocation,
            DeliveryType::GROCERY
        );
        $durationMinutes = $eta ? $eta->diffInMinutes(now()) : null;

        // Find geo zone if not provided
        $zone ??= $this->geofenceService->findGeoZone(
            $deliveryLocation['lat'] ?? 0,
            $deliveryLocation['lng'] ?? 0,
            DeliveryType::GROCERY
        );

        // Get delivery fee from PricingRule (DB) or config (fallback)
        $feeData = $this->getDeliveryFee(
            DeliveryType::GROCERY,
            $zone,
            $distanceKm,
            0, // volume not applicable for grocery
            false, // isUrgent - should be passed from request if needed
            $eta // delivery time for night multiplier
        );

        $deliveryFee = $feeData['total'];

        return [
            'items_subtotal' => $itemsSubtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $itemsSubtotal + $deliveryFee,
            'distance_km' => round($distanceKm, 2),
            'duration_minutes' => $durationMinutes,
            'eta' => $eta,
            'zone_id' => $zone?->id,
            'fee_breakdown' => $feeData,
        ];
    }

    /**
     * Calculate price for food (restaurant) delivery.
     */
    public function calculateForFood(
        array $items,
        array $pickupLocation,
        array $deliveryLocation
    ): array {
        $itemsSubtotal = 0;
        foreach ($items as $item) {
            $itemsSubtotal += ($item['unit_price'] ?? $item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }

        $geoZone = $this->geofenceService->findGeoZone(
            $deliveryLocation['lat'] ?? 0,
            $deliveryLocation['lng'] ?? 0,
            DeliveryType::FOOD
        );

        $route = $this->geofenceService->buildRouteEstimate(
            $pickupLocation,
            $deliveryLocation,
            DeliveryType::FOOD
        );

        $distanceKm = $route['distance_km'] ?? 0;
        $eta = $route['eta'] ?? null;

        // Get delivery fee from PricingRule (DB) or config (fallback)
        $feeData = $this->getDeliveryFee(
            DeliveryType::FOOD,
            $geoZone,
            $distanceKm,
            0, // volume not applicable for food
            false, // isUrgent
            $eta
        );

        $deliveryFee = $feeData['total'];

        return [
            'items_subtotal' => $itemsSubtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $itemsSubtotal + $deliveryFee,
            'distance_km' => $route['distance_km'],
            'duration_minutes' => $route['duration_minutes'],
            'eta' => $route['eta'],
            'zone_id' => $route['geo_zone_id'],
            'fee_breakdown' => $feeData,
        ];
    }

    /**
     * Apply pricing rules for grocery delivery.
     */
    protected function applyPricingRulesForGrocery(float $baseFee, ?GeoZone $zone): float
    {
        $serviceTypeId = $this->resolveServiceTypeId(DeliveryType::GROCERY->value);

        if (! $zone && ! $serviceTypeId) {
            return $baseFee;
        }

        if (! $serviceTypeId) {
            return $this->applyZoneModifier($baseFee, $zone);
        }

        $rules = $this->getPricingRulesForServiceType($serviceTypeId);

        if ($zone) {
            $zoneRule = $rules->first(fn (PricingRule $rule) => $this->ruleMatchesZone($rule, $zone, true));
            if ($zoneRule) {
                return $this->applyPricingRule($baseFee, $zoneRule);
            }
        }

        $genericRule = $rules->first(fn (PricingRule $rule) => $this->ruleMatchesZone($rule, null, false));

        if ($genericRule) {
            return $this->applyPricingRule($baseFee, $genericRule);
        }

        return $this->applyZoneModifier($baseFee, $zone);
    }

    /**
     * Apply pricing rule to base fee.
     */
    protected function applyPricingRule(float $baseFee, PricingRule $rule): float
    {
        $modifiers = $rule->modifiers ?? [];

        // Check pricing model
        $pricingModel = $rule->pricing_model ?? [];

        // If fixed price is set
        if (isset($pricingModel['type']) && $pricingModel['type'] === 'fixed') {
            return $rule->base_price;
        }

        // Apply modifiers
        if (isset($modifiers['multiplier'])) {
            $baseFee *= $modifiers['multiplier'];
        }

        if (isset($modifiers['percent'])) {
            $baseFee *= (1 + $modifiers['percent'] / 100);
        }

        if (isset($modifiers['add'])) {
            $baseFee += $modifiers['add'];
        }

        if (isset($modifiers['subtract'])) {
            $baseFee -= $modifiers['subtract'];
        }

        return max(0, $baseFee);
    }

    /**
     * Unified quote calculator.
     */
    public function calculateQuote(string $type, array $payload): array
    {
        $deliveryType = $this->resolveDeliveryType($type);

        return match ($deliveryType) {
            DeliveryType::GROCERY => $this->buildGroceryQuote($payload),
            DeliveryType::BULKY => $this->buildBulkyQuote($payload),
            DeliveryType::FOOD => $this->buildFoodQuote($payload),
        };
    }

    protected function buildGroceryQuote(array $payload): array
    {
        $pickup = $payload['pickup_location'] ?? [];
        $delivery = $payload['delivery_location'] ?? [];
        $items = $payload['items'] ?? [];

        $pricing = $this->calculateForGrocery($items, $pickup, $delivery);

        return [
            'type' => DeliveryType::GROCERY->value,
            'currency' => 'NOK',
            'breakdown' => [
                'items_subtotal' => round($pricing['items_subtotal'] ?? 0, 2),
                'delivery_fee' => round($pricing['delivery_fee'] ?? 0, 2),
            ],
            'total' => round($pricing['total'] ?? 0, 2),
            'route' => [
                'distance_km' => $pricing['distance_km'] ?? null,
                'duration_minutes' => $pricing['duration_minutes'] ?? null,
                'eta' => $pricing['eta'] ?? null,
                'geo_zone_id' => $pricing['zone_id'] ?? null,
            ],
        ];
    }

    protected function buildBulkyQuote(array $payload): array
    {
        $pickup = $payload['pickup_location'] ?? [];
        $delivery = $payload['delivery_location'] ?? [];
        $dimensions = $payload['dimensions'] ?? [];
        $services = $payload['services'] ?? [];

        $pricing = $this->calculateBulkyPricing($dimensions, $services, $pickup, $delivery);

        return [
            'type' => DeliveryType::BULKY->value,
            'currency' => 'NOK',
            'breakdown' => [
                'base_rate' => $pricing['base_rate'],
                'service_extras' => $pricing['service_extras'],
                'delivery_fee' => $pricing['delivery_fee'],
                'weather_factor' => $pricing['weather_factor'],
            ],
            'total' => $pricing['total'],
            'route' => $pricing['route'] ?? [],
            'meta' => [
                'volume_m3' => $pricing['volume_m3'],
                'geo_zone_id' => $pricing['geo_zone_id'] ?? null,
            ],
        ];
    }

    protected function buildFoodQuote(array $payload): array
    {
        $pickup = $payload['pickup_location'] ?? [];
        $delivery = $payload['delivery_location'] ?? [];
        $items = $payload['food_items'] ?? $payload['items'] ?? [];

        $pricing = $this->calculateForFood($items, $pickup, $delivery);

        return [
            'type' => DeliveryType::FOOD->value,
            'currency' => 'NOK',
            'breakdown' => [
                'items_subtotal' => round($pricing['items_subtotal'] ?? 0, 2),
                'delivery_fee' => round($pricing['delivery_fee'] ?? 0, 2),
            ],
            'total' => round($pricing['total'] ?? 0, 2),
            'route' => [
                'distance_km' => $pricing['distance_km'] ?? null,
                'duration_minutes' => $pricing['duration_minutes'] ?? null,
                'eta' => $pricing['eta'] ?? null,
                'geo_zone_id' => $pricing['zone_id'] ?? null,
            ],
        ];
    }

    /**
     * Get delivery fee for type and zone.
     */
    /**
     * Get delivery fee structure from PricingRule (DB) or config (fallback).
     * Returns array with base_fee, per_km_fee, per_m3_fee, urgency_multiplier, night_multiplier.
     */
    protected function getDeliveryFee(string|DeliveryType $type, ?GeoZone $geoZone = null, float $distanceKm = 0, float $volumeM3 = 0, bool $isUrgent = false, ?\DateTimeInterface $deliveryTime = null): array
    {
        $deliveryType = $this->resolveDeliveryType($type);
        $serviceType = $deliveryType->value;

        // 1. Попробовать найти активное правило в БД
        $rule = PricingRule::query()
            ->where('service_type', $serviceType)
            ->when($geoZone, fn ($q) => $q->where('geo_zone_id', $geoZone->id))
            ->where('is_active', true)
            ->first();

        if (! $rule && $geoZone) {
            // fallback: правило без geo_zone (глобальное)
            $rule = PricingRule::query()
                ->where('service_type', $serviceType)
                ->whereNull('geo_zone_id')
                ->where('is_active', true)
                ->first();
        }

        if ($rule) {
            $baseFee = (float) ($rule->base_fee ?? 0);
            $perKmFee = (float) ($rule->per_km_fee ?? 0);
            $perM3Fee = (float) ($rule->per_m3_fee ?? 0);
            $urgencyMultiplier = (float) ($rule->urgency_multiplier ?? 1.0);
            $nightMultiplier = (float) ($rule->night_multiplier ?? 1.0);

            // Рассчитываем базовую стоимость
            $deliveryPrice = $baseFee + ($perKmFee * max($distanceKm, 0)) + ($perM3Fee * max($volumeM3, 0));

            // Применяем множители
            if ($isUrgent) {
                $deliveryPrice *= $urgencyMultiplier;
            }

            if ($deliveryTime && $this->isNightTime($deliveryTime)) {
                $deliveryPrice *= $nightMultiplier;
            }

            return [
                'base_fee' => $baseFee,
                'per_km_fee' => $perKmFee,
                'per_m3_fee' => $perM3Fee,
                'urgency_multiplier' => $urgencyMultiplier,
                'night_multiplier' => $nightMultiplier,
                'total' => round($deliveryPrice, 2),
            ];
        }

        // 2. Если в БД ничего нет — используем старую конфигурацию
        $typeConfig = config('delivery.types.'.$deliveryType->value, []);
        $baseFee = (float) ($typeConfig['delivery_fee'] ?? $typeConfig['base_rate'] ?? 50);
        $perKmFee = 0;
        $perM3Fee = (float) ($typeConfig['rate_per_m3'] ?? 0);

        $deliveryPrice = $baseFee + ($perKmFee * max($distanceKm, 0)) + ($perM3Fee * max($volumeM3, 0));

        if ($isUrgent) {
            $deliveryPrice *= 1.2; // Default urgency multiplier
        }

        if ($deliveryTime && $this->isNightTime($deliveryTime)) {
            $deliveryPrice *= 1.3; // Default night multiplier
        }

        return [
            'base_fee' => $baseFee,
            'per_km_fee' => $perKmFee,
            'per_m3_fee' => $perM3Fee,
            'urgency_multiplier' => 1.0,
            'night_multiplier' => 1.0,
            'total' => round($deliveryPrice, 2),
        ];
    }

    /**
     * Check if delivery time is in night window (22:00-06:00).
     */
    protected function isNightTime(\DateTimeInterface $time): bool
    {
        $hour = (int) $time->format('H');

        return $hour >= 22 || $hour < 6;
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
     * Resolve a service type id by code/slug.
     */
    protected function resolveServiceTypeId(string $code): ?int
    {
        $cacheKey = "delivery.service_type_id.{$code}";

        return Cache::remember($cacheKey, 3600, function () use ($code) {
            return ServiceType::query()
                ->where('code', $code)
                ->orWhere('slug', $code)
                ->value('id');
        });
    }

    /**
     * Get cached pricing rules for a service type.
     */
    protected function getPricingRulesForServiceType(int $serviceTypeId)
    {
        $cacheKey = "delivery.pricing_rules.{$serviceTypeId}";

        return Cache::remember($cacheKey, 300, function () use ($serviceTypeId) {
            return PricingRule::query()
                ->where('service_type_id', $serviceTypeId)
                ->active()
                ->validFor(now())
                ->orderBy('id')
                ->get();
        });
    }

    /**
     * Determine if a pricing rule applies for the given zone.
     */
    protected function ruleMatchesZone(PricingRule $rule, ?GeoZone $zone, bool $requireZoneMatch = false): bool
    {
        $conditions = $rule->conditions ?? [];
        $zoneConstraint = data_get($conditions, 'geo_zone_id');

        if ($zoneConstraint === null) {
            return $requireZoneMatch ? false : true;
        }

        if (! $zone) {
            return false;
        }

        $allowedIds = collect(Arr::wrap($zoneConstraint))
            ->map(fn ($value) => (int) $value)
            ->all();

        return in_array($zone->id, $allowedIds, true);
    }

    /**
     * Determine if a bulky pricing rule matches the current context.
     */
    protected function ruleMatchesBulkyContext(PricingRule $rule, float $volume, ?GeoZone $geoZone): bool
    {
        $conditions = $rule->conditions ?? [];
        $minVolume = data_get($conditions, 'min_volume');
        $maxVolume = data_get($conditions, 'max_volume');

        if ($minVolume !== null && $volume < (float) $minVolume) {
            return false;
        }

        if ($maxVolume !== null && $volume > (float) $maxVolume) {
            return false;
        }

        if (! $geoZone) {
            return $conditions === [] || data_get($conditions, 'geo_zone_id') === null;
        }

        return $this->ruleMatchesZone($rule, $geoZone);
    }

    /**
     * Apply a zone-specific modifier from metadata if present.
     */
    protected function applyZoneModifier(float $baseFee, ?GeoZone $zone): float
    {
        if ($zone && isset($zone->metadata['delivery_fee_modifier'])) {
            return $baseFee * $zone->metadata['delivery_fee_modifier'];
        }

        return $baseFee;
    }
}
