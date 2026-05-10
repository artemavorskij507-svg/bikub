<?php

namespace App\Services;

use App\Models\GeoZone;
use App\Models\Order;
use App\Models\PricingRule;
use Illuminate\Support\Facades\Log;

class OrderPricingService
{
    /**
     * Calculate order price based on pricing rules and location.
     */
    public function calculateOrderPrice(array $orderData): array
    {
        $totalAmount = 0;
        $items = [];

        foreach ($orderData['items'] as $item) {
            $serviceTypeId = $item['service_type_id'];

            // Get pricing rule for the service type
            $pricingRule = $this->getPricingRuleForService($serviceTypeId, $orderData['location'] ?? null);

            if (! $pricingRule) {
                Log::warning("No pricing rule found for service type ID: {$serviceTypeId}");

                continue;
            }

            // Calculate base price
            $basePrice = $pricingRule->base_price;

            // Apply geo zone modifier if location provided
            $finalPrice = $this->applyGeoZoneModifier($basePrice, $orderData['location'] ?? null);

            // Calculate item total
            $quantity = $item['quantity'] ?? 1;
            $itemTotal = $finalPrice * $quantity;

            $items[] = [
                'service_type_id' => $serviceTypeId,
                'pricing_rule_id' => $pricingRule->id,
                'name' => $item['name'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $finalPrice,
                'total_price' => $itemTotal,
            ];

            $totalAmount += $itemTotal;
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'currency' => 'NOK',
            'items' => $items,
        ];
    }

    /**
     * Get pricing rule for a service type.
     */
    public function getPricingRuleForService(int $serviceTypeId, ?array $location): ?PricingRule
    {
        $query = PricingRule::where('service_type_id', $serviceTypeId)
            ->where('is_active', true)
            ->validFor(now());

        // If location is provided, try to find geo-zone specific pricing
        if ($location && isset($location['lat']) && isset($location['lng'])) {
            $geoZone = $this->findGeoZoneForLocation($location['lat'], $location['lng']);

            if ($geoZone) {
                // First try to find a pricing rule specific to this geo zone
                $geoPricing = $query->clone()
                    ->whereJsonContains('conditions->geo_zone_id', $geoZone->id)
                    ->first();

                if ($geoPricing) {
                    return $geoPricing;
                }
            }
        }

        // Fall back to general pricing rule
        return $query->first();
    }

    /**
     * Apply geo zone modifier to price.
     */
    public function applyGeoZoneModifier(float $basePrice, ?array $location): float
    {
        if (! $location || ! isset($location['lat']) || ! isset($location['lng'])) {
            return $basePrice;
        }

        $geoZone = $this->findGeoZoneForLocation($location['lat'], $location['lng']);

        if (! $geoZone) {
            return $basePrice;
        }

        // Check if pricing rule has modifiers for this geo zone
        $pricingRule = PricingRule::where('service_type_id', 1) // This should be dynamic
            ->active()
            ->first();

        if ($pricingRule && $pricingRule->modifiers) {
            $modifiers = $pricingRule->modifiers;

            // Apply distance-based modifier
            $distance = $geoZone->distanceTo($location['lat'], $location['lng']);

            if (isset($modifiers['per_km'])) {
                $pricePerKm = $modifiers['per_km'];
                $distanceKm = $distance / 1000;
                $basePrice += $pricePerKm * $distanceKm;
            }

            // Apply zone-specific modifier
            if (isset($modifiers['geo_zones'][$geoZone->id])) {
                $zoneModifier = $modifiers['geo_zones'][$geoZone->id];
                $basePrice = $basePrice * (1 + ($zoneModifier / 100));
            }
        }

        return round($basePrice, 2);
    }

    /**
     * Find geo zone for a location.
     */
    public function findGeoZoneForLocation(float $latitude, float $longitude): ?GeoZone
    {
        return GeoZone::active()
            ->get()
            ->first(function ($zone) use ($latitude, $longitude) {
                return $zone->containsPoint($latitude, $longitude);
            });
    }

    /**
     * Calculate estimated delivery time based on distance.
     *
     * @return int Minutes
     */
    public function calculateEstimatedTime(?array $location): int
    {
        if (! $location || ! isset($location['lat']) || ! isset($location['lng'])) {
            return 30; // Default 30 minutes
        }

        $geoZone = $this->findGeoZoneForLocation($location['lat'], $location['lng']);

        if (! $geoZone) {
            return 60; // Default 1 hour for outside zones
        }

        // Base time + distance time
        $baseTime = 15; // 15 minutes base
        $distanceTime = 0;

        if ($geoZone->distanceTo($location['lat'], $location['lng']) > 5000) {
            $distanceTime = 15; // +15 min for >5km
        }

        return $baseTime + $distanceTime;
    }

    /**
     * Get available time slots for a date.
     *
     * @param  string  $date  Y-m-d format
     */
    public function getAvailableTimeSlots(string $date): array
    {
        $slots = [];
        $startHour = 9; // 9:00 AM
        $endHour = 20; // 8:00 PM

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:00', $hour).':'.sprintf('%02d', $minute);
                $slots[] = $date.' '.$time;
            }
        }

        return $slots;
    }
}
