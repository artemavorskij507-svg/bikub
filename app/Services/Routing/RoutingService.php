<?php

namespace App\Services\Routing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingService
{
    protected string $defaultProvider;

    protected ?string $osrmUrl;

    protected ?string $mapboxToken;

    protected array $avgSpeeds;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->defaultProvider = config('routing.default_provider', 'osrm');
        $this->osrmUrl = config('routing.osrm.url');
        $this->mapboxToken = config('routing.mapbox.token');
        $this->avgSpeeds = config('routing.avg_speeds', [
            'car' => 40,
            'bike' => 15,
            'walk' => 5,
        ]);
        $this->cacheTtl = config('routing.cache_ttl_seconds', 30);
    }

    /**
     * Calculate route between two points.
     */
    public function route(Point $from, Point $to, array $opts = []): RouteResult
    {
        $transport = $opts['transport'] ?? 'car';
        $optimize = $opts['optimize'] ?? 'fastest';
        $avoidTolls = $opts['avoid_tolls'] ?? false;

        $cacheKey = $this->getRouteCacheKey($from, $to, $transport, $optimize);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($from, $to, $transport, $optimize) {
            // Try OSRM first
            if ($this->defaultProvider === 'osrm' && $this->osrmUrl) {
                try {
                    $result = $this->routeOsrm($from, $to, $transport, $optimize);
                    if ($result) {
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('OSRM routing failed', ['error' => $e->getMessage()]);
                }
            }

            // Try Mapbox
            if ($this->mapboxToken) {
                try {
                    $result = $this->routeMapbox($from, $to, $transport, $optimize);
                    if ($result) {
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('Mapbox routing failed', ['error' => $e->getMessage()]);
                }
            }

            // Fallback to haversine
            return $this->routeHaversine($from, $to, $transport);
        });
    }

    /**
     * Calculate route using OSRM.
     */
    protected function routeOsrm(Point $from, Point $to, string $transport, string $optimize): ?RouteResult
    {
        $profile = match ($transport) {
            'bike' => 'cycling',
            'walk' => 'foot',
            default => 'driving',
        };

        $url = rtrim($this->osrmUrl, '/')."/route/v1/{$profile}/{$from->lng},{$from->lat};{$to->lng},{$to->lat}";
        $params = [
            'overview' => 'full',
            'geometries' => 'geojson',
            'alternatives' => 'false',
        ];

        if ($optimize === 'shortest') {
            $params['alternatives'] = 'true';
        }

        $response = Http::timeout(5)->get($url, $params);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! isset($data['routes'][0])) {
            return null;
        }

        $route = $data['routes'][0];
        $distanceM = $route['distance'] / 1000; // to km
        $durationS = $route['duration'] / 60; // to minutes

        return new RouteResult(
            distanceKm: round($distanceM, 2),
            durationMin: (int) round($durationS),
            geometry: json_encode($route['geometry'] ?? null),
            steps: [],
            provider: 'osrm',
        );
    }

    /**
     * Calculate route using Mapbox.
     */
    protected function routeMapbox(Point $from, Point $to, string $transport, string $optimize): ?RouteResult
    {
        $profile = match ($transport) {
            'bike' => 'cycling',
            'walk' => 'walking',
            default => 'driving',
        };

        $url = "https://api.mapbox.com/directions/v5/mapbox/{$profile}/{$from->lng},{$from->lat};{$to->lng},{$to->lat}";
        $params = [
            'access_token' => $this->mapboxToken,
            'geometries' => 'geojson',
            'overview' => 'full',
        ];

        $response = Http::timeout(5)->get($url, $params);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! isset($data['routes'][0])) {
            return null;
        }

        $route = $data['routes'][0];
        $distanceM = $route['distance'] / 1000;
        $durationS = $route['duration'] / 60;

        return new RouteResult(
            distanceKm: round($distanceM, 2),
            durationMin: (int) round($durationS),
            geometry: json_encode($route['geometry'] ?? null),
            steps: $route['legs'][0]['steps'] ?? [],
            provider: 'mapbox',
        );
    }

    /**
     * Fallback: calculate straight-line distance using haversine.
     */
    protected function routeHaversine(Point $from, Point $to, string $transport): RouteResult
    {
        $distanceKm = $this->haversineDistance($from->lat, $from->lng, $to->lat, $to->lng);
        $speedKmh = $this->avgSpeeds[$transport] ?? 40;
        $durationMin = (int) round(($distanceKm / $speedKmh) * 60);

        return new RouteResult(
            distanceKm: round($distanceKm, 2),
            durationMin: $durationMin,
            geometry: null,
            steps: [],
            provider: 'internal',
        );
    }

    /**
     * Calculate distance matrix for multiple points.
     */
    public function batchMatrix(array $points, string $transport = 'car'): MatrixResult
    {
        if (count($points) < 2) {
            return new MatrixResult([], [], 'internal');
        }

        $cacheKey = 'osrm:matrix:'.md5(json_encode($points).$transport);

        return Cache::remember($cacheKey, 60, function () use ($points, $transport) {
            // Try OSRM table service
            if ($this->osrmUrl) {
                try {
                    $result = $this->matrixOsrm($points, $transport);
                    if ($result) {
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('OSRM matrix failed', ['error' => $e->getMessage()]);
                }
            }

            // Fallback: calculate pairwise haversine
            return $this->matrixHaversine($points, $transport);
        });
    }

    /**
     * Get matrix from OSRM.
     */
    protected function matrixOsrm(array $points, string $transport): ?MatrixResult
    {
        $profile = match ($transport) {
            'bike' => 'cycling',
            'walk' => 'foot',
            default => 'driving',
        };

        $coords = array_map(fn ($p) => "{$p->lng},{$p->lat}", $points);
        $url = rtrim($this->osrmUrl, '/')."/table/v1/{$profile}/".implode(';', $coords);

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return new MatrixResult(
            distances: $data['distances'] ?? [],
            durations: $data['durations'] ?? [],
            provider: 'osrm',
        );
    }

    /**
     * Fallback matrix using haversine.
     */
    protected function matrixHaversine(array $points, string $transport): MatrixResult
    {
        $distances = [];
        $durations = [];
        $speedKmh = $this->avgSpeeds[$transport] ?? 40;

        foreach ($points as $i => $from) {
            $distances[$i] = [];
            $durations[$i] = [];

            foreach ($points as $j => $to) {
                $distKm = $this->haversineDistance($from->lat, $from->lng, $to->lat, $to->lng);
                $distM = $distKm * 1000;
                $durS = ($distKm / $speedKmh) * 3600;

                $distances[$i][$j] = (int) round($distM);
                $durations[$i][$j] = (int) round($durS);
            }
        }

        return new MatrixResult($distances, $durations, 'internal');
    }

    /**
     * Calculate haversine distance in kilometers.
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Generate cache key for route.
     */
    protected function getRouteCacheKey(Point $from, Point $to, string $transport, string $optimize): string
    {
        return 'route:'.md5("{$from->lat},{$from->lng};{$to->lat},{$to->lng};{$transport};{$optimize}");
    }
}
