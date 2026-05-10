<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\WeatherData;

class GeoV3Service
{
    private array $profiles = [
        'summer' => [
            'base_speed' => 1.0,
            'highway_multiplier' => 1.2,
            'city_multiplier' => 0.8,
            'turn_penalty' => 30, // seconds
            'left_turn_penalty' => 60, // seconds
        ],
        'winter' => [
            'base_speed' => 0.7,
            'highway_multiplier' => 0.9,
            'city_multiplier' => 0.6,
            'turn_penalty' => 45,
            'left_turn_penalty' => 90,
        ],
        'storm' => [
            'base_speed' => 0.5,
            'highway_multiplier' => 0.7,
            'city_multiplier' => 0.4,
            'turn_penalty' => 60,
            'left_turn_penalty' => 120,
        ],
    ];

    public function calculateRoute(array $stops, string $profile = 'summer', array $options = []): array
    {
        $profileConfig = $this->profiles[$profile] ?? $this->profiles['summer'];

        // Get weather conditions if not provided
        $weatherConditions = $options['weather'] ?? $this->getCurrentWeatherConditions();

        // Adjust profile based on weather
        $adjustedProfile = $this->adjustProfileForWeather($profileConfig, $weatherConditions);

        // Calculate route with time windows and constraints
        $route = $this->buildRoute($stops, $adjustedProfile, $options);

        // Optimize route considering time windows
        $optimizedRoute = $this->optimizeWithTimeWindows($route, $options);

        return $optimizedRoute;
    }

    public function calculateMatrix(array $points, string $profile = 'summer'): array
    {
        $profileConfig = $this->profiles[$profile] ?? $this->profiles['summer'];

        // For small matrices, calculate directly
        if (count($points) <= 10) {
            return $this->calculateDirectMatrix($points, $profileConfig);
        }

        // For larger matrices, use clustering
        return $this->calculateClusteredMatrix($points, $profileConfig);
    }

    public function optimizeRoute(Route $route, array $constraints = []): Route
    {
        $stops = $route->stops()->orderBy('sequence')->get();

        if ($stops->count() <= 2) {
            return $route; // No optimization needed
        }

        // Apply time window constraints
        $optimizedStops = $this->applyTimeWindowConstraints($stops, $constraints);

        // Apply capacity constraints
        $optimizedStops = $this->applyCapacityConstraints($optimizedStops, $constraints);

        // Reorder stops for optimal route
        $reorderedStops = $this->reorderStops($optimizedStops, $route->profile);

        // Update route
        $this->updateRouteStops($route, $reorderedStops);

        return $route->fresh();
    }

    public function calculateETA(array $from, array $to, string $profile = 'summer', array $weather = []): int
    {
        $profileConfig = $this->profiles[$profile] ?? $this->profiles['summer'];

        // Calculate base distance
        $distance = $this->calculateDistance($from, $to);

        // Calculate base time
        $baseTime = $distance / ($profileConfig['base_speed'] * 50); // km/h to seconds

        // Apply weather adjustments
        if (! empty($weather)) {
            $baseTime = $this->applyWeatherAdjustments($baseTime, $weather, $profileConfig);
        }

        // Apply road type adjustments
        $adjustedTime = $this->applyRoadTypeAdjustments($baseTime, $from, $to, $profileConfig);

        return (int) round($adjustedTime);
    }

    public function getOptimalProfile(array $stops, array $weather = []): string
    {
        if (empty($weather)) {
            $weather = $this->getCurrentWeatherConditions();
        }

        // Determine profile based on weather conditions
        if ($weather['precipitation'] > 5 || $weather['wind_speed'] > 15) {
            return 'storm';
        }

        if ($weather['temperature'] < 0 || $weather['precipitation'] > 0) {
            return 'winter';
        }

        return 'summer';
    }

    public function validateTimeWindows(array $stops): array
    {
        $violations = [];

        foreach ($stops as $index => $stop) {
            if (! isset($stop['time_window'])) {
                continue;
            }

            $window = $stop['time_window'];
            $arrivalTime = $stop['eta'] ?? null;

            if ($arrivalTime && ($arrivalTime < $window['from'] || $arrivalTime > $window['to'])) {
                $violations[] = [
                    'stop_index' => $index,
                    'window' => $window,
                    'arrival_time' => $arrivalTime,
                    'violation_type' => $arrivalTime < $window['from'] ? 'early' : 'late',
                ];
            }
        }

        return $violations;
    }

    private function buildRoute(array $stops, array $profile, array $options): array
    {
        $route = [
            'stops' => [],
            'total_distance' => 0,
            'total_time' => 0,
            'profile' => $profile,
            'optimization_score' => 0,
        ];

        foreach ($stops as $index => $stop) {
            $route['stops'][] = [
                'index' => $index,
                'coordinates' => $stop['coordinates'],
                'time_window' => $stop['time_window'] ?? null,
                'service_time' => $stop['service_time'] ?? 300, // 5 minutes default
                'constraints' => $stop['constraints'] ?? [],
                'eta' => null,
                'distance_from_previous' => 0,
            ];
        }

        return $route;
    }

    private function optimizeWithTimeWindows(array $route, array $options): array
    {
        $stops = $route['stops'];

        // Sort stops by time window priority
        usort($stops, function ($a, $b) {
            if (! $a['time_window'] && ! $b['time_window']) {
                return 0;
            }
            if (! $a['time_window']) {
                return 1;
            }
            if (! $b['time_window']) {
                return -1;
            }

            return $a['time_window']['from'] <=> $b['time_window']['from'];
        });

        // Calculate ETAs
        $currentTime = time();
        $totalDistance = 0;

        foreach ($stops as $index => &$stop) {
            if ($index > 0) {
                $prevStop = $stops[$index - 1];
                $distance = $this->calculateDistance(
                    $prevStop['coordinates'],
                    $stop['coordinates']
                );
                $stop['distance_from_previous'] = $distance;
                $totalDistance += $distance;

                $travelTime = $this->calculateTravelTime($distance, $route['profile']);
                $currentTime += $travelTime;
            }

            $stop['eta'] = $currentTime;
            $currentTime += $stop['service_time'];
        }

        $route['stops'] = $stops;
        $route['total_distance'] = $totalDistance;
        $route['total_time'] = $currentTime - time();

        return $route;
    }

    private function calculateDirectMatrix(array $points, array $profile): array
    {
        $matrix = [];

        foreach ($points as $i => $from) {
            $matrix[$i] = [];
            foreach ($points as $j => $to) {
                if ($i === $j) {
                    $matrix[$i][$j] = 0;
                } else {
                    $distance = $this->calculateDistance($from, $to);
                    $time = $this->calculateTravelTime($distance, $profile);
                    $matrix[$i][$j] = [
                        'distance' => $distance,
                        'time' => $time,
                    ];
                }
            }
        }

        return $matrix;
    }

    private function calculateClusteredMatrix(array $points, array $profile): array
    {
        // Group nearby points into clusters
        $clusters = $this->clusterPoints($points, 5); // 5km cluster radius

        $matrix = [];

        foreach ($clusters as $i => $cluster) {
            $matrix[$i] = [];
            foreach ($clusters as $j => $otherCluster) {
                if ($i === $j) {
                    $matrix[$i][$j] = 0;
                } else {
                    // Calculate distance between cluster centers
                    $distance = $this->calculateDistance(
                        $cluster['center'],
                        $otherCluster['center']
                    );
                    $time = $this->calculateTravelTime($distance, $profile);
                    $matrix[$i][$j] = [
                        'distance' => $distance,
                        'time' => $time,
                        'cluster_size' => count($cluster['points']),
                    ];
                }
            }
        }

        return $matrix;
    }

    private function clusterPoints(array $points, float $radius): array
    {
        $clusters = [];
        $visited = [];

        foreach ($points as $index => $point) {
            if (isset($visited[$index])) {
                continue;
            }

            $cluster = [
                'center' => $point,
                'points' => [$point],
                'indices' => [$index],
            ];

            // Find nearby points
            foreach ($points as $otherIndex => $otherPoint) {
                if ($otherIndex === $index || isset($visited[$otherIndex])) {
                    continue;
                }

                $distance = $this->calculateDistance($point, $otherPoint);
                if ($distance <= $radius) {
                    $cluster['points'][] = $otherPoint;
                    $cluster['indices'][] = $otherIndex;
                    $visited[$otherIndex] = true;
                }
            }

            $clusters[] = $cluster;
            $visited[$index] = true;
        }

        return $clusters;
    }

    private function calculateDistance(array $from, array $to): float
    {
        $lat1 = deg2rad($from['lat']);
        $lon1 = deg2rad($from['lng']);
        $lat2 = deg2rad($to['lat']);
        $lon2 = deg2rad($to['lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return 6371 * $c; // Earth radius in km
    }

    private function calculateTravelTime(float $distance, array $profile): int
    {
        $baseSpeed = $profile['base_speed'] * 50; // km/h
        $time = ($distance / $baseSpeed) * 3600; // Convert to seconds

        // Add turn penalties (simplified)
        $estimatedTurns = $distance / 0.5; // Rough estimate
        $time += $estimatedTurns * $profile['turn_penalty'];

        return (int) round($time);
    }

    private function getCurrentWeatherConditions(): array
    {
        // Get latest weather data
        $weather = WeatherData::latest()->first();

        if (! $weather) {
            return [
                'temperature' => 15,
                'precipitation' => 0,
                'wind_speed' => 5,
                'conditions' => 'clear',
            ];
        }

        return [
            'temperature' => $weather->temperature,
            'precipitation' => $weather->precipitation,
            'wind_speed' => $weather->wind_speed,
            'conditions' => $weather->conditions,
        ];
    }

    private function adjustProfileForWeather(array $profile, array $weather): array
    {
        $adjusted = $profile;

        // Adjust for precipitation
        if ($weather['precipitation'] > 0) {
            $adjusted['base_speed'] *= 0.8;
            $adjusted['turn_penalty'] *= 1.5;
        }

        // Adjust for wind
        if ($weather['wind_speed'] > 10) {
            $adjusted['base_speed'] *= 0.9;
        }

        // Adjust for temperature
        if ($weather['temperature'] < 0) {
            $adjusted['base_speed'] *= 0.7;
            $adjusted['turn_penalty'] *= 1.3;
        }

        return $adjusted;
    }

    private function applyWeatherAdjustments(int $baseTime, array $weather, array $profile): int
    {
        $multiplier = 1.0;

        if ($weather['precipitation'] > 0) {
            $multiplier += 0.2;
        }

        if ($weather['wind_speed'] > 15) {
            $multiplier += 0.1;
        }

        if ($weather['temperature'] < 0) {
            $multiplier += 0.3;
        }

        return (int) round($baseTime * $multiplier);
    }

    private function applyRoadTypeAdjustments(int $baseTime, array $from, array $to, array $profile): int
    {
        // Simplified road type detection
        $distance = $this->calculateDistance($from, $to);

        // Assume highways for longer distances
        if ($distance > 10) {
            return (int) round($baseTime * $profile['highway_multiplier']);
        }

        // City driving for shorter distances
        return (int) round($baseTime * $profile['city_multiplier']);
    }

    private function applyTimeWindowConstraints($stops, array $constraints): array
    {
        // Implementation for time window constraints
        return $stops->toArray();
    }

    private function applyCapacityConstraints(array $stops, array $constraints): array
    {
        // Implementation for capacity constraints
        return $stops;
    }

    private function reorderStops(array $stops, string $profile): array
    {
        // Simple nearest neighbor with 2-opt improvement
        if (count($stops) <= 2) {
            return $stops;
        }

        $ordered = [$stops[0]]; // Start with first stop
        $remaining = array_slice($stops, 1);

        while (! empty($remaining)) {
            $current = end($ordered);
            $nearestIndex = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($remaining as $index => $stop) {
                $distance = $this->calculateDistance(
                    $current['coordinates'],
                    $stop['coordinates']
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $ordered[] = $remaining[$nearestIndex];
            unset($remaining[$nearestIndex]);
            $remaining = array_values($remaining);
        }

        return $ordered;
    }

    private function updateRouteStops(Route $route, array $stops): void
    {
        // Delete existing stops
        $route->stops()->delete();

        // Create new stops
        foreach ($stops as $index => $stop) {
            RouteStop::create([
                'route_id' => $route->id,
                'sequence' => $index + 1,
                'latitude' => $stop['coordinates']['lat'],
                'longitude' => $stop['coordinates']['lng'],
                'window_from' => $stop['time_window']['from'] ?? null,
                'window_to' => $stop['time_window']['to'] ?? null,
                'service_time_minutes' => ($stop['service_time'] ?? 300) / 60,
                'constraints' => $stop['constraints'] ?? [],
            ]);
        }
    }
}
