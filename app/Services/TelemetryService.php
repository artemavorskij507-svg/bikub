<?php

namespace App\Services;

use App\Models\Geofence;
use App\Models\GeofenceEvent;
use App\Models\Order;
use App\Models\Task;
use App\Models\TelemetryEvent;
use Illuminate\Support\Facades\Log;

class TelemetryService
{
    public function processEvents(array $events): array
    {
        $processedEvents = [];
        $geofenceEvents = [];

        foreach ($events as $eventData) {
            try {
                $event = $this->createTelemetryEvent($eventData);
                $processedEvents[] = $event;

                // Check for geofence events
                $geofenceResults = $this->checkGeofences($event);
                $geofenceEvents = array_merge($geofenceEvents, $geofenceResults);

            } catch (\Exception $e) {
                Log::error('Failed to process telemetry event', [
                    'event_data' => $eventData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed_events' => count($processedEvents),
            'geofence_events' => $geofenceEvents,
            'errors' => count($events) - count($processedEvents),
        ];
    }

    public function updateEtaFromTelemetry(string $resourceId, string $resourceType): ?int
    {
        $resource = $this->getResource($resourceId, $resourceType);
        if (! $resource) {
            return null;
        }

        $latestLocation = $this->getLatestLocation($resourceId, $resourceType);
        if (! $latestLocation) {
            return null;
        }

        $destination = $this->getDestination($resource);
        if (! $destination) {
            return null;
        }

        // Calculate ETA based on current speed and remaining distance
        $remainingDistance = $this->calculateDistance(
            $latestLocation['latitude'],
            $latestLocation['longitude'],
            $destination['latitude'],
            $destination['longitude']
        );

        $currentSpeed = $latestLocation['speed'] ?? 0; // km/h
        if ($currentSpeed <= 0) {
            $currentSpeed = 30; // Default speed if not available
        }

        $etaMinutes = ($remainingDistance / $currentSpeed) * 60;

        // Update resource with new ETA
        $this->updateResourceEta($resource, $etaMinutes);

        return (int) round($etaMinutes);
    }

    public function detectAnomalies(string $resourceId, string $resourceType): array
    {
        $anomalies = [];
        $events = TelemetryEvent::where('resource_id', $resourceId)
            ->where('resource_type', $resourceType)
            ->where('event_timestamp', '>=', now()->subHours(1))
            ->orderBy('event_timestamp')
            ->get();

        if ($events->count() < 3) {
            return $anomalies;
        }

        // Check for speed anomalies
        $speedAnomalies = $this->detectSpeedAnomalies($events);
        $anomalies = array_merge($anomalies, $speedAnomalies);

        // Check for location anomalies
        $locationAnomalies = $this->detectLocationAnomalies($events);
        $anomalies = array_merge($anomalies, $locationAnomalies);

        // Check for DTC codes
        $dtcAnomalies = $this->detectDtcAnomalies($events);
        $anomalies = array_merge($anomalies, $dtcAnomalies);

        return $anomalies;
    }

    public function getRouteOptimization(string $resourceId, string $resourceType): array
    {
        $resource = $this->getResource($resourceId, $resourceType);
        if (! $resource) {
            return [];
        }

        $currentLocation = $this->getLatestLocation($resourceId, $resourceType);
        if (! $currentLocation) {
            return [];
        }

        $waypoints = $this->getWaypoints($resource);
        if (empty($waypoints)) {
            return [];
        }

        // Calculate optimized route
        $optimizedRoute = $this->calculateOptimizedRoute($currentLocation, $waypoints);

        return [
            'current_location' => $currentLocation,
            'waypoints' => $waypoints,
            'optimized_route' => $optimizedRoute,
            'total_distance' => $optimizedRoute['total_distance'] ?? 0,
            'estimated_time' => $optimizedRoute['estimated_time'] ?? 0,
        ];
    }

    private function createTelemetryEvent(array $eventData): TelemetryEvent
    {
        return TelemetryEvent::create([
            'resource_id' => $eventData['resource_id'],
            'resource_type' => $eventData['resource_type'],
            'event_type' => $eventData['event_type'],
            'data' => $eventData['data'] ?? [],
            'latitude' => $eventData['latitude'] ?? null,
            'longitude' => $eventData['longitude'] ?? null,
            'accuracy' => $eventData['accuracy'] ?? null,
            'speed' => $eventData['speed'] ?? null,
            'heading' => $eventData['heading'] ?? null,
            'event_timestamp' => $eventData['event_timestamp'] ?? now(),
            'metadata' => $eventData['metadata'] ?? [],
        ]);
    }

    private function checkGeofences(TelemetryEvent $event): array
    {
        if (! $event->latitude || ! $event->longitude) {
            return [];
        }

        $geofences = Geofence::where('active', true)->get();
        $events = [];

        foreach ($geofences as $geofence) {
            $isInside = $this->isPointInGeofence(
                $event->latitude,
                $event->longitude,
                $geofence
            );

            $wasInside = $this->wasPointInGeofence(
                $event->resource_id,
                $event->resource_type,
                $geofence,
                $event->event_timestamp
            );

            if ($isInside && ! $wasInside) {
                // Entered geofence
                $events[] = $this->createGeofenceEvent($geofence, $event, 'enter');
            } elseif (! $isInside && $wasInside) {
                // Exited geofence
                $events[] = $this->createGeofenceEvent($geofence, $event, 'exit');
            }
        }

        return $events;
    }

    private function isPointInGeofence(float $latitude, float $longitude, Geofence $geofence): bool
    {
        if ($geofence->area) {
            // Check if point is inside polygon
            return $this->isPointInPolygon($latitude, $longitude, $geofence->area);
        }

        if ($geofence->radius_meters) {
            // Check if point is inside circle
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $geofence->center_latitude ?? 0,
                $geofence->center_longitude ?? 0
            ) * 1000; // Convert to meters

            return $distance <= $geofence->radius_meters;
        }

        return false;
    }

    private function wasPointInGeofence(string $resourceId, string $resourceType, Geofence $geofence, $timestamp): bool
    {
        $previousEvent = TelemetryEvent::where('resource_id', $resourceId)
            ->where('resource_type', $resourceType)
            ->where('event_timestamp', '<', $timestamp)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('event_timestamp', 'desc')
            ->first();

        if (! $previousEvent) {
            return false;
        }

        return $this->isPointInGeofence(
            $previousEvent->latitude,
            $previousEvent->longitude,
            $geofence
        );
    }

    private function createGeofenceEvent(Geofence $geofence, TelemetryEvent $event, string $eventType): GeofenceEvent
    {
        return GeofenceEvent::create([
            'geofence_id' => $geofence->id,
            'resource_id' => $event->resource_id,
            'resource_type' => $event->resource_type,
            'event_type' => $eventType,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'event_timestamp' => $event->event_timestamp,
            'metadata' => [
                'telemetry_event_id' => $event->id,
                'geofence_name' => $geofence->name,
            ],
        ]);
    }

    private function detectSpeedAnomalies($events): array
    {
        $anomalies = [];
        $speeds = $events->where('speed', '>', 0)->pluck('speed')->toArray();

        if (count($speeds) < 3) {
            return $anomalies;
        }

        $avgSpeed = array_sum($speeds) / count($speeds);
        $threshold = $avgSpeed * 2; // 200% of average speed

        foreach ($events as $event) {
            if ($event->speed && $event->speed > $threshold) {
                $anomalies[] = [
                    'type' => 'speed_anomaly',
                    'event_id' => $event->id,
                    'speed' => $event->speed,
                    'threshold' => $threshold,
                    'severity' => 'high',
                ];
            }
        }

        return $anomalies;
    }

    private function detectLocationAnomalies($events): array
    {
        $anomalies = [];
        $locations = $events->whereNotNull('latitude')->whereNotNull('longitude');

        if ($locations->count() < 3) {
            return $anomalies;
        }

        $previousLocation = null;
        foreach ($locations as $event) {
            if ($previousLocation) {
                $distance = $this->calculateDistance(
                    $previousLocation->latitude,
                    $previousLocation->longitude,
                    $event->latitude,
                    $event->longitude
                );

                $timeDiff = $event->event_timestamp->diffInSeconds($previousLocation->event_timestamp);
                $speed = $timeDiff > 0 ? ($distance / $timeDiff) * 3600 : 0; // km/h

                // Check for impossible speed (>200 km/h)
                if ($speed > 200) {
                    $anomalies[] = [
                        'type' => 'location_anomaly',
                        'event_id' => $event->id,
                        'calculated_speed' => $speed,
                        'distance' => $distance,
                        'time_diff' => $timeDiff,
                        'severity' => 'critical',
                    ];
                }
            }
            $previousLocation = $event;
        }

        return $anomalies;
    }

    private function detectDtcAnomalies($events): array
    {
        $anomalies = [];
        $dtcEvents = $events->where('event_type', 'dtc_code');

        foreach ($dtcEvents as $event) {
            $dtcCode = $event->data['code'] ?? null;
            if ($dtcCode) {
                $anomalies[] = [
                    'type' => 'dtc_anomaly',
                    'event_id' => $event->id,
                    'dtc_code' => $dtcCode,
                    'description' => $event->data['description'] ?? 'Unknown DTC',
                    'severity' => $this->getDtcSeverity($dtcCode),
                ];
            }
        }

        return $anomalies;
    }

    private function getDtcSeverity(string $dtcCode): string
    {
        // Map DTC codes to severity levels
        $criticalCodes = ['P0001', 'P0002', 'P0003']; // Critical engine codes
        $warningCodes = ['P0100', 'P0101', 'P0102']; // Warning codes

        if (in_array($dtcCode, $criticalCodes)) {
            return 'critical';
        } elseif (in_array($dtcCode, $warningCodes)) {
            return 'warning';
        }

        return 'info';
    }

    private function getResource(string $resourceId, string $resourceType)
    {
        return match ($resourceType) {
            'order' => Order::find($resourceId),
            'task' => Task::find($resourceId),
            default => null
        };
    }

    private function getLatestLocation(string $resourceId, string $resourceType): ?array
    {
        $event = TelemetryEvent::where('resource_id', $resourceId)
            ->where('resource_type', $resourceType)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('event_timestamp', 'desc')
            ->first();

        if (! $event) {
            return null;
        }

        return [
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'speed' => $event->speed,
            'heading' => $event->heading,
            'timestamp' => $event->event_timestamp,
        ];
    }

    private function getDestination($resource): ?array
    {
        if ($resource instanceof Order) {
            return [
                'latitude' => $resource->delivery_latitude,
                'longitude' => $resource->delivery_longitude,
            ];
        }

        if ($resource instanceof Task) {
            return [
                'latitude' => $resource->destination_latitude,
                'longitude' => $resource->destination_longitude,
            ];
        }

        return null;
    }

    private function getWaypoints($resource): array
    {
        // Implementation to get waypoints for route optimization
        return [];
    }

    private function calculateOptimizedRoute(array $currentLocation, array $waypoints): array
    {
        // Implementation for route optimization algorithm
        return [
            'total_distance' => 0,
            'estimated_time' => 0,
            'route' => [],
        ];
    }

    private function updateResourceEta($resource, int $etaMinutes): void
    {
        if ($resource instanceof Order) {
            $resource->update(['estimated_delivery' => now()->addMinutes($etaMinutes)]);
        } elseif ($resource instanceof Task) {
            $resource->update(['estimated_completion' => now()->addMinutes($etaMinutes)]);
        }
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isPointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $x = $longitude;
        $y = $latitude;
        $inside = false;

        $j = count($polygon) - 1;
        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
            $j = $i;
        }

        return $inside;
    }
}
