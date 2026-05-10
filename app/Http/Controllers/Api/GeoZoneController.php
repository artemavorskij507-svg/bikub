<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoZone;
use App\Services\Geo\GeoZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeoZoneController extends Controller
{
    public function __construct(
        protected GeoZoneService $geoZoneService,
    ) {}

    /**
     * GET /api/v1/geo/zones
     */
    public function index(Request $request): JsonResponse
    {
        $query = GeoZone::active();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('bbox')) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                [$minLat, $minLng, $maxLat, $maxLng] = array_map('floatval', $bbox);
                // Simple bbox filter (would need proper spatial index for production)
                $query->where(function ($q) {
                    // This is a simplified check - in production use PostGIS
                });
            }
        }

        $zones = $query->orderBy('priority')->get();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $zones->map(function (GeoZone $zone) {
                return [
                    'type' => 'Feature',
                    'id' => $zone->id,
                    'properties' => [
                        'name' => $zone->name,
                        'slug' => $zone->slug,
                        'type' => $zone->type,
                        'meta' => $zone->meta,
                        'priority' => $zone->priority,
                    ],
                    'geometry' => $zone->geometry ?? [
                        'type' => 'Polygon',
                        'coordinates' => [],
                    ],
                ];
            })->toArray(),
        ];

        return response()->json($geojson);
    }

    /**
     * GET /api/v1/geo/zones/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $zone = GeoZone::where('slug', $slug)->first();

        if (! $zone) {
            return response()->json([
                'success' => false,
                'message' => 'Geo zone not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $zone,
            'message' => 'Geo zone retrieved successfully',
        ]);
    }

    /**
     * POST /api/v1/geo/zone/contains
     */
    public function contains(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;

        $matches = $this->geoZoneService->findZonesForPoint($lat, $lng);
        $nearest = $this->geoZoneService->findNearestZones($lat, $lng, 5);

        return response()->json([
            'success' => true,
            'matches' => $matches->map(function (GeoZone $zone) {
                return [
                    'zone_id' => $zone->id,
                    'name' => $zone->name,
                    'slug' => $zone->slug,
                    'meta' => $zone->meta,
                ];
            }),
            'nearest_zones' => $nearest->map(function (GeoZone $zone) use ($lat, $lng) {
                return [
                    'zone_id' => $zone->id,
                    'name' => $zone->name,
                    'slug' => $zone->slug,
                    'distance_m' => round($zone->distanceTo($lat, $lng)),
                    'meta' => $zone->meta,
                ];
            }),
        ]);
    }
}
