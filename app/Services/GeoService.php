<?php

namespace App\Services;

use App\Models\Address;
use App\Models\GeoZone;
use App\Models\RouteMatrix;
use App\Models\SlaPolicy;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoService
{
    private string $osrmUrl;

    private bool $useOsrm;

    public function __construct()
    {
        $this->osrmUrl = config('services.osrm.url', 'http://localhost:5000');
        $this->useOsrm = config('services.osrm.enabled', false);
    }

    /**
     * Calculate distance and duration between two points.
     */
    public function calculateRoute(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $mode = 'driving'
    ): array {
        // Check cache first
        $cacheKey = $this->getCacheKey($fromLat, $fromLng, $toLat, $toLng, $mode);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $result = $this->useOsrm
            ? $this->calculateWithOsrm($fromLat, $fromLng, $toLat, $toLng, $mode)
            : $this->calculateWithHaversine($fromLat, $fromLng, $toLat, $toLng, $mode);

        // Cache for 1 hour
        Cache::put($cacheKey, $result, 3600);

        // Store in database for analytics
        RouteMatrix::updateOrCreate(
            [
                'from_lat' => $fromLat,
                'from_lng' => $fromLng,
                'to_lat' => $toLat,
                'to_lng' => $toLng,
                'mode' => $mode,
            ],
            [
                'from_address' => $this->reverseGeocode($fromLat, $fromLng),
                'to_address' => $this->reverseGeocode($toLat, $toLng),
                'distance_meters' => $result['distance'],
                'duration_seconds' => $result['duration'],
                'route_data' => $result['route_data'] ?? null,
                'cached_at' => now(),
            ]
        );

        return $result;
    }

    /**
     * Calculate route using OSRM (recommended).
     */
    private function calculateWithOsrm(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $mode
    ): array {
        try {
            $profile = match ($mode) {
                'driving' => 'driving',
                'walking' => 'walking',
                'cycling' => 'cycling',
                default => 'driving',
            };

            $url = "{$this->osrmUrl}/route/v1/{$profile}/{$fromLng},{$fromLat};{$toLng},{$toLat}";
            $params = [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'true',
            ];

            $response = Http::timeout(5)->get($url, $params);

            if (! $response->successful()) {
                throw new \Exception('OSRM API error: '.$response->body());
            }

            $data = $response->json();

            if (empty($data['routes'])) {
                throw new \Exception('No route found');
            }

            $route = $data['routes'][0];

            return [
                'distance' => (int) $route['distance'],
                'duration' => (int) $route['duration'],
                'route_data' => $route,
                'method' => 'osrm',
            ];

        } catch (\Exception $e) {
            Log::warning('OSRM calculation failed, falling back to Haversine', [
                'error' => $e->getMessage(),
                'from' => [$fromLat, $fromLng],
                'to' => [$toLat, $toLng],
            ]);

            return $this->calculateWithHaversine($fromLat, $fromLng, $toLat, $toLng, $mode);
        }
    }

    /**
     * Calculate route using Haversine formula (fallback).
     */
    private function calculateWithHaversine(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $mode
    ): array {
        $distance = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);

        // Estimate duration based on mode
        $speedKmh = match ($mode) {
            'driving' => 30, // Average city speed
            'walking' => 5,
            'cycling' => 15,
            default => 30,
        };

        $duration = ($distance / 1000) / $speedKmh * 3600; // Convert to seconds

        return [
            'distance' => (int) $distance,
            'duration' => (int) $duration,
            'method' => 'haversine',
        ];
    }

    /**
     * Calculate Haversine distance between two points.
     */
    private function haversineDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find geo zone for given coordinates.
     */
    public function findGeoZone(float $lat, float $lng): ?GeoZone
    {
        // First check circular zones
        $circularZone = GeoZone::whereNotNull('radius_meters')
            ->where('is_active', true)
            ->get()
            ->first(function ($zone) use ($lat, $lng) {
                $distance = $this->haversineDistance(
                    $zone->center_lat,
                    $zone->center_lng,
                    $lat,
                    $lng
                );

                return $distance <= $zone->radius_meters;
            });

        if ($circularZone) {
            return $circularZone;
        }

        // Then check polygon zones (simplified - would need proper polygon intersection)
        return GeoZone::whereNull('radius_meters')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate ETA with weather and SLA adjustments.
     */
    public function calculateEta(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        ?SlaPolicy $slaPolicy = null,
        ?string $locationCode = null
    ): array {
        $baseRoute = $this->calculateRoute($fromLat, $fromLng, $toLat, $toLng);

        $baseDuration = $baseRoute['duration'];
        $adjustedDuration = $baseDuration;
        $adjustments = [];

        // Apply SLA policy adjustments
        if ($slaPolicy) {
            // Night coefficient (22:00 - 06:00)
            $isNight = $this->isNightTime();
            if ($isNight) {
                $nightAdjustment = $slaPolicy->night_coef - 1.0;
                $adjustedDuration *= $slaPolicy->night_coef;
                $adjustments[] = [
                    'type' => 'night',
                    'coefficient' => $slaPolicy->night_coef,
                    'adjustment' => $nightAdjustment * $baseDuration,
                ];
            }

            // Weather coefficient
            if ($locationCode) {
                $weather = $this->getCurrentWeather($locationCode);
                if ($weather && $weather['condition'] === 'snow') {
                    $snowAdjustment = $slaPolicy->snow_coef - 1.0;
                    $adjustedDuration *= $slaPolicy->snow_coef;
                    $adjustments[] = [
                        'type' => 'snow',
                        'coefficient' => $slaPolicy->snow_coef,
                        'adjustment' => $snowAdjustment * $baseDuration,
                    ];
                }
            }
        }

        return [
            'base_duration' => $baseDuration,
            'adjusted_duration' => (int) $adjustedDuration,
            'adjustments' => $adjustments,
            'eta' => now()->addSeconds($adjustedDuration),
            'confidence' => $this->calculateConfidence($baseRoute['method'], $adjustments),
        ];
    }

    /**
     * Optimize route for multiple stops.
     */
    public function optimizeRoute(array $stops): array
    {
        if (count($stops) < 3) {
            return $stops; // No optimization needed
        }

        // Simple nearest neighbor algorithm
        $optimized = [];
        $remaining = $stops;
        $current = array_shift($remaining);

        $optimized[] = $current;

        while (! empty($remaining)) {
            $nearest = $this->findNearestStop($current, $remaining);
            $optimized[] = $nearest;

            // Remove from remaining
            $remaining = array_filter($remaining, function ($stop) use ($nearest) {
                return $stop['id'] !== $nearest['id'];
            });

            $current = $nearest;
        }

        return $optimized;
    }

    /**
     * Get current weather for location.
     */
    public function getCurrentWeather(string $locationCode): ?array
    {
        $cacheKey = "weather_{$locationCode}_".now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () use ($locationCode) {
            $weather = WeatherData::where('location_code', $locationCode)
                ->where('date', now()->toDateString())
                ->where('time', '>=', now()->subHour())
                ->orderBy('time', 'desc')
                ->first();

            if (! $weather) {
                // Fetch from external API (placeholder)
                return $this->fetchWeatherFromApi($locationCode);
            }

            return [
                'temperature' => $weather->temperature,
                'humidity' => $weather->humidity,
                'wind_speed' => $weather->wind_speed,
                'precipitation' => $weather->precipitation,
                'condition' => $weather->condition,
            ];
        });
    }

    /**
     * Check if current time is night.
     */
    private function isNightTime(): bool
    {
        $hour = now()->hour;

        return $hour >= 22 || $hour < 6;
    }

    /**
     * Calculate confidence score for ETA.
     */
    private function calculateConfidence(string $method, array $adjustments): float
    {
        $baseConfidence = match ($method) {
            'osrm' => 0.9,
            'haversine' => 0.6,
            default => 0.5,
        };

        // Reduce confidence based on adjustments
        $adjustmentPenalty = count($adjustments) * 0.05;

        return max(0.3, $baseConfidence - $adjustmentPenalty);
    }

    /**
     * Find nearest stop to current position.
     */
    private function findNearestStop(array $current, array $stops): array
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($stops as $stop) {
            $distance = $this->haversineDistance(
                $current['lat'],
                $current['lng'],
                $stop['lat'],
                $stop['lng']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $stop;
            }
        }

        return $nearest;
    }

    /**
     * Generate cache key for route calculation.
     */
    private function getCacheKey(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $mode
    ): string {
        return 'route_'.md5("{$fromLat}_{$fromLng}_{$toLat}_{$toLng}_{$mode}");
    }

    /**
     * Reverse geocode coordinates to address.
     */
    private function reverseGeocode(float $lat, float $lng): string
    {
        // Placeholder - would integrate with geocoding service
        return "Lat: {$lat}, Lng: {$lng}";
    }

    /**
     * Fetch weather from external API.
     */
    private function fetchWeatherFromApi(string $locationCode): ?array
    {
        // Placeholder - would integrate with weather API
        return null;
    }
}
