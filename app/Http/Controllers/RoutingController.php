<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function calculateMatrix(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Matrix calculation endpoint is not implemented in this controller yet.',
        ], 501);
    }

    public function recalculateEta(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'ETA recalculation endpoint is not implemented in this controller yet.',
            'route_id' => $id,
        ], 501);
    }

    /**
     * Create a new route.
     */
    public function createRoute(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'vehicle_id' => 'nullable|string',
            'orders' => 'required|array|min:1',
            'orders.*' => 'exists:orders,id',
        ]);

        DB::beginTransaction();
        try {
            // Create route
            $route = Route::create([
                'date' => $request->date,
                'vehicle_id' => $request->vehicle_id,
                'meta' => [
                    'created_by' => auth()->id(),
                    'algorithm' => 'manual',
                    'winter_protocol' => Cache::get('winter_protocol.enabled', false),
                ],
            ]);

            // Group orders by zone and create stops
            $orders = Order::whereIn('id', $request->orders)
                ->with(['location', 'orderItems.serviceType'])
                ->get();

            $stops = $this->optimizeRouteStops($orders);

            foreach ($stops as $index => $order) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'order_id' => $order->id,
                    'seq' => $index + 1,
                    'eta' => $this->calculateEta($order, $index, $stops),
                    'eta_confidence' => $this->calculateEtaConfidence($order, $index, $stops),
                    'meta' => [
                        'zone' => $this->getOrderZone($order),
                        'service_types' => $order->orderItems->pluck('serviceType.name')->toArray(),
                        'estimated_duration' => $this->estimateServiceDuration($order),
                    ],
                ]);
            }

            // Update order statuses
            Order::whereIn('id', $request->orders)->update([
                'status' => 'confirmed',
                'metadata' => array_merge(Order::whereIn('id', $request->orders)->first()->metadata ?? [], [
                    'route_id' => $route->id,
                    'routed_at' => now()->toISOString(),
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $route->load(['routeStops.order.user', 'routeStops.order.orderItems.serviceType']),
                'message' => 'Route created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create route',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update route stops order.
     */
    public function updateRouteStops(Request $request, string $routeId)
    {
        $request->validate([
            'stops' => 'required|array|min:1',
            'stops.*.order_id' => 'required|exists:orders,id',
            'stops.*.seq' => 'required|integer|min:1',
        ]);

        $route = Route::findOrFail($routeId);

        DB::beginTransaction();
        try {
            // Delete existing stops
            RouteStop::where('route_id', $route->id)->delete();

            // Create new stops
            foreach ($request->stops as $stopData) {
                $order = Order::findOrFail($stopData['order_id']);

                RouteStop::create([
                    'route_id' => $route->id,
                    'order_id' => $stopData['order_id'],
                    'seq' => $stopData['seq'],
                    'eta' => $this->calculateEta($order, $stopData['seq'] - 1, $request->stops),
                    'eta_confidence' => $this->calculateEtaConfidence($order, $stopData['seq'] - 1, $request->stops),
                    'meta' => [
                        'zone' => $this->getOrderZone($order),
                        'service_types' => $order->orderItems->pluck('serviceType.name')->toArray(),
                        'estimated_duration' => $this->estimateServiceDuration($order),
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $route->fresh(['routeStops.order.user', 'routeStops.order.orderItems.serviceType']),
                'message' => 'Route stops updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update route stops',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get route details with ETA.
     */
    public function getRoute(string $routeId)
    {
        $route = Route::with([
            'routeStops.order.user',
            'routeStops.order.orderItems.serviceType',
            'routeStops.order.location',
        ])->findOrFail($routeId);

        $stops = $route->routeStops->map(function ($stop) {
            return [
                'id' => $stop->id,
                'seq' => $stop->seq,
                'eta' => $stop->eta,
                'eta_confidence' => $stop->eta_confidence,
                'order' => [
                    'id' => $stop->order->id,
                    'order_number' => $stop->order->order_number,
                    'status' => $stop->order->status,
                    'priority' => $stop->order->priority,
                    'location' => $stop->order->location,
                    'customer' => [
                        'name' => $stop->order->user->name,
                        'phone' => $stop->order->user->phone,
                    ],
                    'services' => $stop->order->orderItems->map(function ($item) {
                        return [
                            'name' => $item->serviceType->name,
                            'duration' => $item->serviceType->estimated_duration_minutes ?? 30,
                        ];
                    }),
                ],
                'meta' => $stop->meta,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'route' => [
                    'id' => $route->id,
                    'date' => $route->date,
                    'vehicle_id' => $route->vehicle_id,
                    'total_eta' => $route->total_eta,
                    'efficiency' => $route->getEfficiencyMetrics(),
                ],
                'stops' => $stops,
            ],
        ]);
    }

    /**
     * Optimize route stops using simple nearest neighbor algorithm.
     */
    private function optimizeRouteStops($orders)
    {
        if ($orders->isEmpty()) {
            return collect();
        }

        // Group by zones first
        $zones = $orders->groupBy(function ($order) {
            return $this->getOrderZone($order);
        });

        $optimizedStops = collect();

        // Process each zone
        foreach ($zones as $zoneOrders) {
            $zoneStops = $this->optimizeZoneStops($zoneOrders);
            $optimizedStops = $optimizedStops->merge($zoneStops);
        }

        return $optimizedStops;
    }

    /**
     * Optimize stops within a zone using nearest neighbor.
     */
    private function optimizeZoneStops($orders)
    {
        if ($orders->count() <= 1) {
            return $orders;
        }

        $stops = $orders->toArray();
        $optimized = [];
        $visited = [];

        // Start with the first order
        $current = $stops[0];
        $optimized[] = $current;
        $visited[0] = true;

        // Find nearest neighbors
        while (count($optimized) < count($stops)) {
            $nearestIndex = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($stops as $index => $stop) {
                if (isset($visited[$index])) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $current['location']['lat'] ?? 0,
                    $current['location']['lng'] ?? 0,
                    $stop['location']['lat'] ?? 0,
                    $stop['location']['lng'] ?? 0
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            if ($nearestIndex !== null) {
                $current = $stops[$nearestIndex];
                $optimized[] = $current;
                $visited[$nearestIndex] = true;
            }
        }

        return collect($optimized);
    }

    /**
     * Calculate ETA for a stop.
     */
    private function calculateEta($order, $index, $allStops)
    {
        $baseTime = now()->setTime(8, 0); // Start at 8 AM
        $minutesPerStop = 30; // Base 30 minutes per stop
        $travelTime = 15; // Base 15 minutes travel time

        // Add time for previous stops
        $totalMinutes = ($index * $minutesPerStop) + ($index * $travelTime);

        // Add service duration
        $serviceDuration = $this->estimateServiceDuration($order);
        $totalMinutes += $serviceDuration;

        // Apply winter protocol multiplier
        $winterProtocol = Cache::get('winter_protocol', ['enabled' => false, 'eta_multiplier' => 1.2]);
        if ($winterProtocol['enabled']) {
            $totalMinutes *= $winterProtocol['eta_multiplier'];
        }

        return $baseTime->addMinutes($totalMinutes);
    }

    /**
     * Calculate ETA confidence.
     */
    private function calculateEtaConfidence($order, $index, $allStops)
    {
        $confidence = 0.8; // Base confidence

        // Reduce confidence for complex orders
        $serviceCount = $order->orderItems->count();
        if ($serviceCount > 3) {
            $confidence -= 0.1;
        }

        // Reduce confidence for longer routes
        if ($index > 5) {
            $confidence -= 0.05;
        }

        // Weather factor
        $winterProtocol = Cache::get('winter_protocol', ['enabled' => false]);
        if ($winterProtocol['enabled']) {
            $confidence -= 0.1;
        }

        return max(0.1, min(1.0, $confidence));
    }

    /**
     * Get order zone based on location.
     */
    private function getOrderZone($order)
    {
        $location = $order->location ?? [];
        if (empty($location)) {
            return 'unknown';
        }

        // Simple zone calculation based on coordinates
        $lat = $location['lat'] ?? 0;
        $lng = $location['lng'] ?? 0;

        // This is a simplified zone calculation
        // In production, you'd use proper geo-zones
        if ($lat > 68.5) {
            return 'north';
        } elseif ($lat < 68.0) {
            return 'south';
        } else {
            return 'center';
        }
    }

    /**
     * Estimate service duration for an order.
     */
    private function estimateServiceDuration($order)
    {
        $totalDuration = 0;

        foreach ($order->orderItems as $item) {
            $duration = $item->serviceType->estimated_duration_minutes ?? 30;
            $totalDuration += $duration;
        }

        return $totalDuration;
    }

    /**
     * Calculate distance between two points.
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
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
}
