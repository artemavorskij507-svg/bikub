<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Moving\MovingOrder;
use App\Models\Order;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Moving\MovingPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MovingOrderController extends Controller
{
    protected MovingPriceCalculator $priceCalculator;

    public function __construct(MovingPriceCalculator $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Create a new moving order.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Customer info
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',

            // From address
            'from_address' => 'required|array',
            'from_address.street' => 'required|string|max:255',
            'from_address.city' => 'required|string|max:100',
            'from_address.postal_code' => 'required|string|max:10',
            'from_address.lat' => 'nullable|numeric|between:-90,90',
            'from_address.lng' => 'nullable|numeric|between:-180,180',
            'from_address.floor' => 'nullable|integer|min:0',
            'from_address.has_elevator' => 'nullable|boolean',

            // To address
            'to_address' => 'required|array',
            'to_address.street' => 'required|string|max:255',
            'to_address.city' => 'required|string|max:100',
            'to_address.postal_code' => 'required|string|max:10',
            'to_address.lat' => 'nullable|numeric|between:-90,90',
            'to_address.lng' => 'nullable|numeric|between:-180,180',
            'to_address.floor' => 'nullable|integer|min:0',
            'to_address.has_elevator' => 'nullable|boolean',

            // Moving details
            'package_type' => 'required|in:economy,standard,premium',
            'rooms' => 'required|in:studio,1br,2br,house',
            'services' => 'nullable|array',
            'services.packing' => 'nullable|boolean',
            'services.assembly' => 'nullable|boolean',
            'services.disassembly' => 'nullable|boolean',
            'services.wrapping' => 'nullable|boolean',

            // Schedule
            'scheduled_at' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'distance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('Moving order validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['customer.password']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create or find customer - update if exists
            $customer = User::firstOrCreate(
                ['email' => $request->customer['email']],
                [
                    'name' => $request->customer['name'],
                    'phone' => $request->customer['phone'],
                    'password' => bcrypt(uniqid()),
                    'is_active' => true,
                ]
            );

            // Update customer info if changed
            $updated = false;
            if ($customer->name !== $request->customer['name']) {
                $customer->name = $request->customer['name'];
                $updated = true;
            }
            if ($customer->phone !== $request->customer['phone']) {
                $customer->phone = $request->customer['phone'];
                $updated = true;
            }
            if ($updated) {
                $customer->save();
            }

            // Default coordinates for Narvik if not provided
            $fromLat = $request->from_address['lat'] ?? 68.4372;
            $fromLng = $request->from_address['lng'] ?? 17.4256;
            $toLat = $request->to_address['lat'] ?? 68.4372;
            $toLng = $request->to_address['lng'] ?? 17.4256;

            // Create from address - check for duplicates
            $fromAddress = Address::firstOrCreate(
                [
                    'street_address' => $request->from_address['street'],
                    'postal_code' => $request->from_address['postal_code'],
                    'city' => $request->from_address['city'],
                ],
                [
                    'latitude' => $fromLat,
                    'longitude' => $fromLng,
                    'formatted_address' => $this->formatAddress($request->from_address),
                    'meta' => [
                        'floor' => $request->from_address['floor'] ?? null,
                        'has_elevator' => $request->from_address['has_elevator'] ?? false,
                    ],
                ]
            );

            // Update coordinates if they were provided
            if ($fromAddress->latitude != $fromLat || $fromAddress->longitude != $fromLng) {
                $fromAddress->update([
                    'latitude' => $fromLat,
                    'longitude' => $fromLng,
                ]);
            }

            // Create to address - check for duplicates
            $toAddress = Address::firstOrCreate(
                [
                    'street_address' => $request->to_address['street'],
                    'postal_code' => $request->to_address['postal_code'],
                    'city' => $request->to_address['city'],
                ],
                [
                    'latitude' => $toLat,
                    'longitude' => $toLng,
                    'formatted_address' => $this->formatAddress($request->to_address),
                    'meta' => [
                        'floor' => $request->to_address['floor'] ?? null,
                        'has_elevator' => $request->to_address['has_elevator'] ?? false,
                    ],
                ]
            );

            // Update coordinates if they were provided
            if ($toAddress->latitude != $toLat || $toAddress->longitude != $toLng) {
                $toAddress->update([
                    'latitude' => $toLat,
                    'longitude' => $toLng,
                ]);
            }

            // Find moving service type - optimized search
            $movingService = ServiceType::where(function ($q) {
                $q->where('code', 'moving')
                    ->orWhere('slug', 'moving')
                    ->orWhere('category', 'moving');
            })
                ->whereHas('serviceCategory', function ($q) {
                    $q->where('slug', 'moving');
                })
                ->active()
                ->first();

            // Fallback: try without category if not found
            if (! $movingService) {
                $movingService = ServiceType::where(function ($q) {
                    $q->where('code', 'moving')
                        ->orWhere('slug', 'moving')
                        ->orWhere('category', 'moving');
                })
                    ->active()
                    ->first();
            }

            if (! $movingService) {
                Log::error('Moving service type not found', [
                    'available_services' => ServiceType::active()->pluck('code', 'slug')->toArray(),
                ]);
                throw new \Exception('Moving service type not found. Please contact support.');
            }

            // Calculate volume based on rooms
            $volumeMap = [
                'studio' => 10,
                '1br' => 20,
                '2br' => 35,
                'house' => 60,
            ];
            $totalVolume = $volumeMap[$request->rooms] ?? 20;

            // Create main Order
            $order = Order::create([
                'user_id' => $customer->id,
                'address_id' => $toAddress->id, // Delivery address
                'service_type' => $movingService->code ?? 'moving',
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
                'total_amount' => 0, // Will be calculated
                'currency' => 'NOK',
                'metadata' => [
                    'moving_type' => $request->rooms,
                    'package_type' => $request->package_type,
                    'services' => $request->services ?? [],
                    'from_address_id' => $fromAddress->id,
                    'to_address_id' => $toAddress->id,
                    'distance_km' => $this->calculateDistance($fromLat, $fromLng, $toLat, $toLng),
                    'created_via' => 'web_landing',
                ],
            ]);

            // Create MovingOrder
            $movingOrder = MovingOrder::create([
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'status' => 'pending',
                'from_address' => [
                    'street' => $request->from_address['street'],
                    'city' => $request->from_address['city'],
                    'postal_code' => $request->from_address['postal_code'],
                    'lat' => $fromLat,
                    'lng' => $fromLng,
                    'floor' => $request->from_address['floor'] ?? null,
                    'has_elevator' => $request->from_address['has_elevator'] ?? false,
                ],
                'to_address' => [
                    'street' => $request->to_address['street'],
                    'city' => $request->to_address['city'],
                    'postal_code' => $request->to_address['postal_code'],
                    'lat' => $toLat,
                    'lng' => $toLng,
                    'floor' => $request->to_address['floor'] ?? null,
                    'has_elevator' => $request->to_address['has_elevator'] ?? false,
                ],
                'package_type' => $request->package_type,
                'services' => $request->services ?? [],
                'total_volume' => $totalVolume,
                'scheduled_at' => $request->scheduled_at,
                'customer_notes' => $request->notes,
                'metadata' => [
                    'rooms' => $request->rooms,
                    'created_via' => 'web_landing',
                ],
            ]);

            // Calculate price with error handling
            try {
                $estimatedPrice = $this->priceCalculator->calculate($movingOrder);
                if ($estimatedPrice <= 0) {
                    Log::warning('Moving order price calculation returned 0 or negative', [
                        'moving_order_id' => $movingOrder->id,
                    ]);
                    $estimatedPrice = config('moving.base_price', 500); // Fallback to base price
                }
            } catch (\Exception $e) {
                Log::error('Price calculation failed, using fallback', [
                    'error' => $e->getMessage(),
                    'moving_order_id' => $movingOrder->id,
                ]);
                $estimatedPrice = config('moving.base_price', 500); // Fallback to base price
            }

            $movingOrder->update(['estimated_price' => $estimatedPrice]);
            $order->update(['total_amount' => $estimatedPrice]);

            // Create OrderItem
            $order->orderItems()->create([
                'service_type_id' => $movingService->id,
                'quantity' => 1,
                'name' => 'Moving Service',
                'unit_price' => $estimatedPrice,
                'total_price' => $estimatedPrice,
                'currency' => 'NOK',
                'metadata' => [
                    'moving_type' => $request->rooms,
                    'package_type' => $request->package_type,
                    'services' => $request->services ?? [],
                ],
            ]);

            DB::commit();

            Log::info('Moving order created', [
                'order_id' => $order->id,
                'moving_order_id' => $movingOrder->id,
                'user_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'moving_order_id' => $movingOrder->id,
                    'order_number' => $order->order_number,
                    'estimated_price' => round($estimatedPrice, 2),
                    'currency' => 'NOK',
                    'status' => $order->status,
                    'scheduled_at' => $order->scheduled_at?->format('Y-m-d H:i:s'),
                    'from_address' => $this->formatAddress($request->from_address),
                    'to_address' => $this->formatAddress($request->to_address),
                    'distance_km' => $this->calculateDistance($fromLat, $fromLng, $toLat, $toLng),
                    'total_volume' => $totalVolume,
                    'package_type' => $request->package_type,
                ],
                'message' => 'Moving order created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create moving order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create moving order',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Format address string.
     */
    protected function formatAddress(array $address): string
    {
        return sprintf(
            '%s, %s %s',
            $address['street'],
            $address['postal_code'],
            $address['city']
        );
    }

    /**
     * Calculate distance between two coordinates (Haversine formula).
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
