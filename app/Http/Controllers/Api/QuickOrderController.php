<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickOrderRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\ServiceType;
use App\Services\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuickOrderController extends Controller
{
    protected GeoService $geoService;

    public function __construct(GeoService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Create a quick order from the fast order form.
     */
    public function store(QuickOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get or create user (for guest orders, we might need to create a guest user)
            $user = Auth::user();

            // If no authenticated user, we'll need to handle guest orders
            // For now, we'll require authentication
            if (! $user) {
                return response()->json([
                    'message' => 'Потрібна авторизація для створення замовлення',
                ], 401);
            }

            // Get service type
            $serviceType = ServiceType::findOrFail($request->service_type_id);

            // Geocode address (simplified - in production, use proper geocoding service)
            $geocoded = $this->geocodeAddress($request->address);

            // Create or find address
            $address = Address::firstOrCreate(
                [
                    'formatted_address' => $request->address,
                ],
                [
                    'street_address' => $this->extractStreetAddress($request->address),
                    'city' => $this->extractCity($request->address),
                    'postal_code' => $this->extractPostalCode($request->address),
                    'country' => 'NO',
                    'latitude' => $geocoded['lat'] ?? null,
                    'longitude' => $geocoded['lng'] ?? null,
                    'formatted_address' => $request->address,
                ]
            );

            // Determine geo zone based on coordinates (if available)
            $geoZoneId = null;
            if ($geocoded['lat'] && $geocoded['lng']) {
                $geoZoneId = $this->findGeoZone($geocoded['lat'], $geocoded['lng']);
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'priority' => 'normal',
                'address_id' => $address->id,
                'geo_zone_id' => $geoZoneId,
                'location' => [
                    'delivery' => [
                        'address' => $request->address,
                        'lat' => $geocoded['lat'] ?? null,
                        'lng' => $geocoded['lng'] ?? null,
                    ],
                ],
                'currency' => 'NOK',
                'payment_status' => 'pending',
            ]);

            // Create order item for the service type
            $order->orderItems()->create([
                'service_type_id' => $serviceType->id,
                'name' => $serviceType->name,
                'description' => $serviceType->description,
                'quantity' => 1,
                'unit_price' => 0, // Will be calculated later
                'total_price' => 0,
                'currency' => 'NOK',
            ]);

            DB::commit();

            Log::info('Quick order created', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'service_type_id' => $serviceType->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Замовлення створено успішно',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Quick order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Помилка при створенні замовлення. Спробуйте пізніше.',
            ], 500);
        }
    }

    /**
     * Simple geocoding (placeholder - in production, use Mapbox or similar)
     */
    protected function geocodeAddress(string $address): array
    {
        // Placeholder - in production, integrate with Mapbox Geocoding API
        // For now, return empty coordinates
        return [
            'lat' => null,
            'lng' => null,
        ];
    }

    /**
     * Extract street address from full address string.
     */
    protected function extractStreetAddress(string $address): string
    {
        // Simple extraction - in production, use proper address parsing
        $parts = explode(',', $address);

        return trim($parts[0] ?? $address);
    }

    /**
     * Extract city from full address string.
     */
    protected function extractCity(string $address): string
    {
        $parts = explode(',', $address);

        return trim($parts[1] ?? '');
    }

    /**
     * Extract postal code from full address string.
     */
    protected function extractPostalCode(string $address): string
    {
        // Try to find postal code pattern (Norwegian format: 4 digits)
        if (preg_match('/\b\d{4}\b/', $address, $matches)) {
            return $matches[0];
        }

        return '';
    }

    /**
     * Find geo zone based on coordinates.
     */
    protected function findGeoZone(?float $lat, ?float $lng): ?int
    {
        if (! $lat || ! $lng) {
            return null;
        }

        // In production, use spatial query to find zone containing the point
        // For now, return null
        return null;
    }
}
