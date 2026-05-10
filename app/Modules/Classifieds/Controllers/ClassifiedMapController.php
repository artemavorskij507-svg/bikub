<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Services\MapboxGeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClassifiedMapController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'classifieds_map_'.md5(json_encode($request->all()));

        return Cache::remember($cacheKey, 300, function () use ($request) {
            $query = ClassifiedAd::published();

            // Filter by bounding box: minLng, minLat, maxLng, maxLat
            if ($request->filled('bbox')) {
                $bbox = array_map('floatval', explode(',', $request->input('bbox')));
                if (count($bbox) === 4) {
                    [$minLng, $minLat, $maxLng, $maxLat] = $bbox;
                    $query
                        ->whereBetween('lat', [$minLat, $maxLat])
                        ->whereBetween('lng', [$minLng, $maxLng]);
                }
            } elseif ($request->filled(['lat', 'lng', 'radius'])) {
                $query->nearby(
                    (float) $request->input('lat'),
                    (float) $request->input('lng'),
                    (int) $request->input('radius')
                );
            }

            $ads = $query
                ->select(['id', 'title', 'slug', 'price_value', 'lat', 'lng'])
                ->limit(500)
                ->get();

            $features = $ads
                ->filter(fn ($ad) => $ad->lng !== null && $ad->lat !== null)
                ->map(function (ClassifiedAd $ad) {
                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$ad->lng, $ad->lat],
                        ],
                        'properties' => [
                            'id' => $ad->id,
                            'title' => $ad->title,
                            'price' => $ad->priceFormatted,
                            'url' => url("/classifieds/{$ad->slug}"),
                        ],
                    ];
                });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features->values(),
            ]);
        });
    }

    public function geocode(Request $request, MapboxGeocodingService $service)
    {
        $validated = $request->validate([
            'address' => 'required|string|min:3',
        ]);

        $coords = $service->getCoordinates($validated['address']);

        if (! $coords) {
            return response()->json(['error' => 'Address not found'], 404);
        }

        return response()->json($coords);
    }
}
