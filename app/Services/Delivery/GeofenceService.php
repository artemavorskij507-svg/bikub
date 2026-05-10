<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryType;
use App\Models\GeoZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GeofenceService
{
    /**
     * Estimate delivery time based on locations and type.
     */
    public function estimateDeliveryTime(array $from, array $to, string|DeliveryType $type): Carbon
    {
        $deliveryType = $this->resolveDeliveryType($type);

        // Calculate distance
        $distance = $this->calculateDistanceInternal(
            $from['lat'] ?? 0,
            $from['lng'] ?? 0,
            $to['lat'] ?? 0,
            $to['lng'] ?? 0
        );

        // Get base time and time per km from config
        $typeConfig = config('delivery.types.'.$deliveryType->value, []);
        $baseTime = $typeConfig['base_time'] ?? 15; // minutes
        $timePerKm = $typeConfig['time_per_km'] ?? 2; // minutes per km

        // Calculate estimated time
        $estimatedMinutes = $baseTime + ($distance * $timePerKm);

        // Apply geo zone modifiers if available
        $geoZone = $this->findGeoZone($to['lat'] ?? 0, $to['lng'] ?? 0, $deliveryType);
        if ($geoZone) {
            $estimatedMinutes = $this->applyGeoZoneModifier($estimatedMinutes, $geoZone, $deliveryType);
        }

        return now()->addMinutes((int) round($estimatedMinutes));
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    public function calculateDistance(array $from, array $to): float
    {
        return $this->calculateDistanceInternal(
            $from['lat'] ?? 0,
            $from['lng'] ?? 0,
            $to['lat'] ?? 0,
            $to['lng'] ?? 0
        );
    }

    /**
     * Calculate distance between two points using Haversine formula (internal).
     */
    protected function calculateDistanceInternal(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find geo zone for a location.
     */
    public function findGeoZone(float $latitude, float $longitude, ?DeliveryType $type = null): ?GeoZone
    {
        $cacheKey = $this->buildGeoZoneCacheKey($type, $latitude, $longitude);

        return Cache::remember(
            $cacheKey,
            3600,
            function () use ($latitude, $longitude, $type) {
                return GeoZone::where('is_active', true)
                    ->get()
                    ->first(function ($zone) use ($latitude, $longitude, $type) {
                        return $zone->containsPoint($latitude, $longitude)
                            && $this->zoneSupportsType($zone, $type);
                    });
            }
        );
    }

    /**
     * Apply geo zone modifier to estimated time.
     */
    protected function applyGeoZoneModifier(float $minutes, GeoZone $zone, DeliveryType $type): float
    {
        $modifier = $zone->metadata['delivery_time_modifier'] ?? 1.0;

        // Apply type-specific modifiers if available
        if (isset($zone->metadata['delivery_modifiers'][$type->value])) {
            $modifier = $zone->metadata['delivery_modifiers'][$type->value];
        }

        return $minutes * $modifier;
    }

    /**
     * Build unified route estimate.
     */
    public function buildRouteEstimate(array $from, array $to, string|DeliveryType $type): array
    {
        $deliveryType = $this->resolveDeliveryType($type);

        $distanceKm = $this->calculateDistance($from, $to);
        $eta = $this->estimateDeliveryTime($from, $to, $deliveryType);
        $geoZone = $this->findGeoZone($to['lat'] ?? 0, $to['lng'] ?? 0, $deliveryType);

        return [
            'distance_km' => round($distanceKm, 2),
            'duration_minutes' => $eta ? $eta->diffInMinutes(now()) : null,
            'eta' => $eta,
            'geo_zone_id' => $geoZone?->id,
            'geo_zone_name' => $geoZone->name ?? null,
        ];
    }

    /**
     * Get delivery zones for a type.
     */
    public function getZonesForType(string|DeliveryType $type): \Illuminate\Support\Collection
    {
        $deliveryType = $this->resolveDeliveryType($type);

        return Cache::remember(
            "delivery.zones.{$deliveryType->value}",
            3600,
            function () use ($deliveryType) {
                return GeoZone::where('is_active', true)
                    ->where('type', $deliveryType->value)
                    ->get();
            }
        );
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
     * Determine if geo zone can serve delivery type.
     */
    protected function zoneSupportsType(GeoZone $zone, ?DeliveryType $type): bool
    {
        if (! $type) {
            return true;
        }

        if ($zone->type === null || $zone->type === $type->value) {
            return true;
        }

        $allowedTypes = data_get($zone->metadata, 'allowed_types');
        if (is_array($allowedTypes) && in_array($type->value, array_map('strval', $allowedTypes), true)) {
            return true;
        }

        return false;
    }

    /**
     * Build cache key for geo zone lookup.
     */
    protected function buildGeoZoneCacheKey(?DeliveryType $type, float $latitude, float $longitude): string
    {
        $precisionLat = number_format($latitude, 4, '.', '');
        $precisionLng = number_format($longitude, 4, '.', '');

        return sprintf(
            'geozone.%s.%s.%s',
            $type?->value ?? 'any',
            $precisionLat,
            $precisionLng
        );
    }
}
