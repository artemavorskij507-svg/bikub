<?php

namespace App\Services\Moving;

use App\Models\GeoZone;
use App\Models\Moving\MovingOrder;
use App\Models\PricingRule;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Cache;

class MovingPriceCalculator
{
    /**
     * Calculate total price for a moving order.
     */
    public function calculate(MovingOrder $order): float
    {
        // Для новых заказов (без ID) не используем кэш
        if (! $order->id) {
            return $this->calculatePrice($order);
        }

        $cacheKey = "moving:price:{$order->id}:".md5(json_encode($order->only(['from_address', 'to_address', 'services', 'package_type', 'total_volume', 'total_weight'])));

        return Cache::remember($cacheKey, 3600, function () use ($order) {
            return $this->calculatePrice($order);
        });
    }

    /**
     * Internal price calculation method.
     */
    protected function calculatePrice(MovingOrder $order): float
    {
        try {
            // Find geo zone for from address
            $fromGeoZone = $this->findGeoZone(
                $order->from_address['lat'] ?? 0,
                $order->from_address['lng'] ?? 0
            );

            // Get base tariff for moving service
            $baseTariff = $this->getBaseTariff($fromGeoZone);

            // Base price
            $basePrice = $baseTariff->base_price ?? config('moving.base_price', 500);

            // Volume-based pricing
            $volumePrice = $this->calculateVolumePrice($order, $baseTariff);

            // Service extras
            $servicePrice = $this->calculateServicePrice($order, $baseTariff);

            // Floor surcharge
            $floorSurcharge = $this->calculateFloorSurcharge($order, $baseTariff);

            // Distance surcharge (if different zones)
            $distanceSurcharge = $this->calculateDistanceSurcharge($order, $baseTariff);

            // Package type multiplier
            $packageMultiplier = $this->getPackageMultiplier($order->package_type ?? 'standard');

            $total = ($basePrice + $volumePrice + $servicePrice + $floorSurcharge + $distanceSurcharge) * $packageMultiplier;

            // Apply weather factor
            $weatherFactor = $this->getWeatherCoefficient();

            $finalPrice = round($total * $weatherFactor, 2);

            // Минимальная цена
            $minPrice = config('moving.min_price', 300);

            return max($finalPrice, $minPrice);
        } catch (\Exception $e) {
            \Log::error('Moving price calculation error', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // Возвращаем базовую цену при ошибке
            return config('moving.base_price', 500);
        }
    }

    /**
     * Get base tariff for moving service.
     */
    protected function getBaseTariff(?GeoZone $geoZone): ?PricingRule
    {
        $serviceType = ServiceType::where('code', 'moving')
            ->orWhere('name', 'like', '%переїзд%')
            ->first();

        if (! $serviceType) {
            return null;
        }

        $query = PricingRule::where('service_type_id', $serviceType->id)
            ->where('is_active', true)
            ->validFor(now());

        if ($geoZone) {
            // Try to find rule for specific geo zone
            $query->where(function ($q) use ($geoZone) {
                $q->whereJsonContains('conditions->geo_zone_id', $geoZone->id)
                    ->orWhereNull('conditions->geo_zone_id');
            });
        }

        return $query->orderBy('base_price', 'asc')->first();
    }

    /**
     * Calculate volume-based price.
     */
    protected function calculateVolumePrice(MovingOrder $order, ?PricingRule $baseTariff): float
    {
        $volume = $order->total_volume ?? $order->calculateTotalVolume();

        if ($volume <= 0) {
            return 0;
        }

        $pricePerM3 = $baseTariff?->pricing_model['price_per_m3']
            ?? config('moving.price_per_m3', 50);

        return $volume * $pricePerM3;
    }

    /**
     * Calculate service extras price.
     */
    protected function calculateServicePrice(MovingOrder $order, ?PricingRule $baseTariff): float
    {
        if (! $order->services) {
            return 0;
        }

        $servicePrices = $baseTariff?->pricing_model['service_prices']
            ?? config('moving.service_prices', [
                'assembly' => 100,
                'disassembly' => 80,
                'packaging' => 50,
                'wrapping' => 30,
                'takelage' => 150,
                'electronics' => 120,
            ]);

        $total = 0;
        foreach ($order->services as $service => $enabled) {
            if ($enabled) {
                // Map frontend service names to config keys
                $serviceKey = match ($service) {
                    'packing' => 'packaging',
                    'wrapping' => 'wrapping',
                    'assembly' => 'assembly',
                    'disassembly' => 'disassembly',
                    default => $service,
                };

                if (isset($servicePrices[$serviceKey])) {
                    $total += $servicePrices[$serviceKey];
                } elseif (isset($servicePrices[$service])) {
                    $total += $servicePrices[$service];
                }
            }
        }

        return $total;
    }

    /**
     * Calculate floor surcharge.
     */
    protected function calculateFloorSurcharge(MovingOrder $order, ?PricingRule $baseTariff): float
    {
        $fromFloor = $order->from_address['floor'] ?? 0;
        $toFloor = $order->to_address['floor'] ?? 0;
        $hasElevator = ($order->from_address['has_elevator'] ?? false)
            && ($order->to_address['has_elevator'] ?? false);

        if ($hasElevator || ($fromFloor <= 1 && $toFloor <= 1)) {
            return 0;
        }

        $surchargePerFloor = $baseTariff?->pricing_model['floor_surcharge']
            ?? config('moving.floor_surcharge', 50);

        $totalFloors = max(0, ($fromFloor - 1) + ($toFloor - 1));

        return $totalFloors * $surchargePerFloor;
    }

    /**
     * Calculate distance surcharge.
     */
    protected function calculateDistanceSurcharge(MovingOrder $order, ?PricingRule $baseTariff): float
    {
        $fromLat = $order->from_address['lat'] ?? 0;
        $fromLng = $order->from_address['lng'] ?? 0;
        $toLat = $order->to_address['lat'] ?? 0;
        $toLng = $order->to_address['lng'] ?? 0;

        if (! $fromLat || ! $fromLng || ! $toLat || ! $toLng) {
            return 0;
        }

        $distance = $this->calculateDistance($fromLat, $fromLng, $toLat, $toLng);

        // Free distance included
        $freeDistance = $baseTariff?->pricing_model['free_distance_km']
            ?? config('moving.free_distance_km', 5);

        if ($distance <= $freeDistance) {
            return 0;
        }

        $pricePerKm = $baseTariff?->pricing_model['price_per_km']
            ?? config('moving.price_per_km', 10);

        return ($distance - $freeDistance) * $pricePerKm;
    }

    /**
     * Calculate distance between two points in kilometers.
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get package type multiplier.
     */
    protected function getPackageMultiplier(string $packageType): float
    {
        return match ($packageType) {
            'economy' => 0.9,
            'standard' => 1.0,
            'premium' => 1.2,
            default => 1.0,
        };
    }

    /**
     * Get weather coefficient.
     */
    protected function getWeatherCoefficient(): float
    {
        return Cache::remember('weather.coefficient', 3600, function () {
            // In real implementation, fetch from WeatherData model
            // For now, return default
            return config('moving.weather.default_coefficient', 1.0);
        });
    }

    /**
     * Find geo zone for location.
     */
    protected function findGeoZone(float $latitude, float $longitude): ?GeoZone
    {
        return Cache::remember(
            "geozone.{$latitude}.{$longitude}",
            3600,
            function () use ($latitude, $longitude) {
                return GeoZone::where('is_active', true)
                    ->get()
                    ->first(function ($zone) use ($latitude, $longitude) {
                        return $zone->containsPoint($latitude, $longitude);
                    });
            }
        );
    }
}
