<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;

class DeliveryPriceController extends Controller
{
    protected OrderPricingService $pricingService;

    public function __construct(OrderPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate delivery price.
     */
    public function calculate(Request $request)
    {
        try {
            // Validate input with proper constraints
            $validated = $request->validate([
                'mode' => 'required|string|in:small,bulky,food',
                'zone' => 'nullable|string|in:narvik_center,narvik_nord,bjerkvik,ankerli',
                'distance' => 'nullable|numeric|min:0|max:500', // Max 500km
                'weight' => 'nullable|numeric|min:0|max:5000', // Max 5000kg
                'restaurant_id' => 'nullable|integer|min:1',
                'items' => 'nullable|array|max:100',
                'items.*.id' => 'required|integer|min:1',
                'items.*.quantity' => 'required|integer|min:1|max:999',
            ]);

            $basePrice = 0;
            $distancePrice = 0;
            $weightPrice = 0;

            // Base price by mode
            switch ($validated['mode']) {
                case 'small':
                    $basePrice = 49; // Base delivery fee for groceries
                    break;
                case 'bulky':
                    $basePrice = 199; // Base delivery fee for cargo
                    break;
                case 'food':
                    $basePrice = 39; // Base delivery fee for food
                    break;
            }

            // Distance-based pricing
            if (! empty($validated['distance'])) {
                $distance = (float) $validated['distance'];
                if ($validated['mode'] === 'bulky') {
                    $distancePrice = $distance * 25; // 25 kr per km for cargo
                } else {
                    $distancePrice = $distance * 15; // 15 kr per km for small/food
                }
            }

            // Weight-based pricing (for cargo only)
            if ($validated['mode'] === 'bulky' && ! empty($validated['weight'])) {
                $weight = (float) $validated['weight'];
                if ($weight > 20) {
                    $weightPrice = ($weight - 20) * 5; // 5 kr per kg over 20kg
                }
            }

            // Zone modifier with validation
            $zoneModifier = 1.0;
            if (! empty($validated['zone'])) {
                $zones = [
                    'narvik_center' => 1.0,
                    'narvik_nord' => 1.2,
                    'bjerkvik' => 1.2,
                    'ankerli' => 1.3,
                ];
                $zoneModifier = $zones[$validated['zone']] ?? 1.0;
            }

            // Calculate total
            $subtotal = $basePrice + $distancePrice + $weightPrice;
            $total = round($subtotal * $zoneModifier, 2);

            // Ensure total is positive
            if ($total < 0) {
                throw new \InvalidArgumentException('Calculated price cannot be negative');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'base_price' => $basePrice,
                    'distance_price' => round($distancePrice, 2),
                    'weight_price' => round($weightPrice, 2),
                    'zone_modifier' => $zoneModifier,
                    'subtotal' => round($subtotal, 2),
                    'total' => $total,
                    'currency' => 'NOK',
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('Delivery price validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            \Illuminate\Support\Facades\Log::error('Delivery price calculation error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error calculating delivery price', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price. Please try again.',
            ], 500);
        }
    }
}
