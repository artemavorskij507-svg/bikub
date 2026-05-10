<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Geo\GeoZoneService;
use App\Services\Pricing\OrderContext;
use App\Services\Pricing\PriceEngine;
use App\Services\Routing\Point;
use App\Services\Routing\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    public function __construct(
        protected RoutingService $routingService,
        protected GeoZoneService $geoZoneService,
        protected PriceEngine $priceEngine,
    ) {}

    /**
     * POST /api/v1/route/estimate
     */
    public function estimate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from.lat' => 'required|numeric|between:-90,90',
            'from.lng' => 'required|numeric|between:-180,180',
            'to.lat' => 'required|numeric|between:-90,90',
            'to.lng' => 'required|numeric|between:-180,180',
            'transport' => 'nullable|in:car,bike,walk',
            'optimize' => 'nullable|in:fastest,shortest',
            'avoid_tolls' => 'nullable|boolean',
            'service_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $from = Point::fromArray($request->from);
        $to = Point::fromArray($request->to);
        $transport = $request->input('transport', 'car');
        $optimize = $request->input('optimize', 'fastest');
        $avoidTolls = $request->input('avoid_tolls', false);

        $routeResult = $this->routingService->route($from, $to, [
            'transport' => $transport,
            'optimize' => $optimize,
            'avoid_tolls' => $avoidTolls,
        ]);

        // Find zones for route
        $geometry = $routeResult->geometry ? json_decode($routeResult->geometry, true) : null;
        $coords = $geometry['coordinates'] ?? [[$from->lat, $from->lng], [$to->lat, $to->lng]];

        $zones = $this->geoZoneService->findZoneForRoute($coords);

        // Price hint if service_type provided
        $priceHint = null;
        if ($request->has('service_type')) {
            try {
                $context = OrderContext::fromArray([
                    'service_type' => $request->service_type,
                    'distance_km' => $routeResult->distanceKm,
                    'from' => $from->toArray(),
                    'to' => $to->toArray(),
                    'zone' => $zones->first()?->slug,
                ]);

                $priceResult = $this->priceEngine->estimate($context);
                $priceHint = [
                    'subtotal' => $priceResult->subtotal,
                    'total' => $priceResult->total,
                    'breakdown' => $priceResult->breakdown,
                ];
            } catch (\Exception $e) {
                // Price estimation failed, continue without it
            }
        }

        return response()->json([
            'success' => true,
            'distance_km' => $routeResult->distanceKm,
            'duration_min' => $routeResult->durationMin,
            'geometry' => $routeResult->geometry,
            'eta' => now()->addMinutes($routeResult->durationMin)->toIso8601String(),
            'zones' => $zones->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'slug' => $zone->slug,
                    'meta' => $zone->meta,
                ];
            }),
            'price_hint' => $priceHint,
            'provider' => $routeResult->provider,
        ]);
    }

    /**
     * POST /api/v1/route/matrix
     */
    public function matrix(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|array|min:2',
            'points.*.lat' => 'required|numeric|between:-90,90',
            'points.*.lng' => 'required|numeric|between:-180,180',
            'transport' => 'nullable|in:car,bike,walk',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $points = array_map(fn ($p) => Point::fromArray($p), $request->points);
        $transport = $request->input('transport', 'car');

        $matrixResult = $this->routingService->batchMatrix($points, $transport);

        return response()->json([
            'success' => true,
            'distances' => $matrixResult->distances,
            'durations' => $matrixResult->durations,
            'provider' => $matrixResult->provider,
        ]);
    }
}
