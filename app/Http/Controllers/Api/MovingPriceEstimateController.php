<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Moving\MovingOrder;
use App\Services\Moving\MovingPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovingPriceEstimateController extends Controller
{
    protected MovingPriceCalculator $priceCalculator;

    public function __construct(MovingPriceCalculator $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Calculate price estimate for moving order without creating it.
     */
    public function estimate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_address' => 'required|array',
            'from_address.lat' => 'nullable|numeric|between:-90,90',
            'from_address.lng' => 'nullable|numeric|between:-180,180',
            'from_address.floor' => 'nullable|integer|min:0',
            'from_address.has_elevator' => 'nullable|boolean',

            'to_address' => 'required|array',
            'to_address.lat' => 'nullable|numeric|between:-90,90',
            'to_address.lng' => 'nullable|numeric|between:-180,180',
            'to_address.floor' => 'nullable|integer|min:0',
            'to_address.has_elevator' => 'nullable|boolean',

            'package_type' => 'required|in:economy,standard,premium',
            'rooms' => 'required|in:studio,1br,2br,house',
            'services' => 'nullable|array',
            'services.packing' => 'nullable|boolean',
            'services.assembly' => 'nullable|boolean',
            'services.disassembly' => 'nullable|boolean',
            'services.wrapping' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Calculate volume based on rooms
            $volumeMap = [
                'studio' => 10,
                '1br' => 20,
                '2br' => 35,
                'house' => 60,
            ];
            $totalVolume = $volumeMap[$request->rooms] ?? 20;

            // Default coordinates for Narvik if not provided
            $fromLat = $request->from_address['lat'] ?? 68.4372;
            $fromLng = $request->from_address['lng'] ?? 17.4256;
            $toLat = $request->to_address['lat'] ?? 68.4372;
            $toLng = $request->to_address['lng'] ?? 17.4256;

            // Create temporary MovingOrder for calculation
            $tempOrder = new MovingOrder([
                'from_address' => [
                    'lat' => $fromLat,
                    'lng' => $fromLng,
                    'floor' => $request->from_address['floor'] ?? null,
                    'has_elevator' => $request->from_address['has_elevator'] ?? false,
                ],
                'to_address' => [
                    'lat' => $toLat,
                    'lng' => $toLng,
                    'floor' => $request->to_address['floor'] ?? null,
                    'has_elevator' => $request->to_address['has_elevator'] ?? false,
                ],
                'package_type' => $request->package_type,
                'services' => $request->services ?? [],
                'total_volume' => $totalVolume,
            ]);

            // Calculate price
            $estimatedPrice = $this->priceCalculator->calculate($tempOrder);

            // Calculate distance
            $distance = $this->calculateDistance($fromLat, $fromLng, $toLat, $toLng);

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_price' => round($estimatedPrice, 2),
                    'currency' => 'NOK',
                    'distance_km' => round($distance, 2),
                    'volume_m3' => $totalVolume,
                    'breakdown' => [
                        'base_price' => 500,
                        'volume_price' => $totalVolume * 50,
                        'distance_price' => max(0, ($distance - 5) * 10),
                        'services_price' => $this->calculateServicesPrice($request->services ?? []),
                        'package_multiplier' => $this->getPackageMultiplier($request->package_type),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price estimate',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Calculate distance between two points in kilometers.
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
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

    /**
     * Calculate services price.
     */
    protected function calculateServicesPrice(array $services): float
    {
        $servicePrices = [
            'packing' => 50,
            'assembly' => 100,
            'disassembly' => 80,
            'wrapping' => 30,
        ];

        $total = 0;
        foreach ($services as $service => $enabled) {
            if ($enabled && isset($servicePrices[$service])) {
                $total += $servicePrices[$service];
            }
        }

        return $total;
    }

    /**
     * Get package type multiplier.
     */
    protected function getPackageMultiplier(string $packageType): float
    {
        return match ($packageType) {
            'economy' => 0.9,
            'standard' => 1.0,
            'premium' => 1.2,
            default => 1.0,
        };
    }
}
