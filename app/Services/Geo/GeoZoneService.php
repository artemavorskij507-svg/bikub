<?php

namespace App\Services\Geo;

use App\Models\GeoZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeoZoneService
{
    protected const CACHE_KEY = 'geo:active_zones';

    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Find all zones that contain a point.
     */
    public function findZonesForPoint(float $lat, float $lng): Collection
    {
        $zones = $this->getActiveZones();

        return $zones->filter(function (GeoZone $zone) use ($lat, $lng) {
            return $zone->containsPoint($lat, $lng);
        })->sortBy('priority');
    }

    /**
     * Find zones that intersect with a route.
     */
    public function findZoneForRoute(array $polylineOrCoords): Collection
    {
        $zones = $this->getActiveZones();
        $matches = collect();

        foreach ($zones as $zone) {
            if ($zone->intersectsLineString($polylineOrCoords)) {
                $matches->push($zone);
            }
        }

        return $matches->sortBy('priority');
    }

    /**
     * Get all active zones from cache or database.
     */
    public function getActiveZones(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return GeoZone::active()
                ->orderBy('priority')
                ->get();
        });
    }

    /**
     * Refresh the cache of active zones.
     */
    public function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        $zones = GeoZone::active()
            ->orderBy('priority')
            ->get();

        Cache::put(self::CACHE_KEY, $zones, self::CACHE_TTL);

        Log::info('GeoZone cache refreshed', [
            'zones_count' => $zones->count(),
        ]);
    }

    /**
     * Find nearest zones to a point (even if not containing).
     */
    public function findNearestZones(float $lat, float $lng, int $limit = 5): Collection
    {
        $zones = $this->getActiveZones();

        return $zones->map(function (GeoZone $zone) use ($lat, $lng) {
            $distance = $zone->distanceTo($lat, $lng);

            return [
                'zone' => $zone,
                'distance_m' => $distance,
            ];
        })->sortBy('distance_m')->take($limit)->pluck('zone');
    }
}
