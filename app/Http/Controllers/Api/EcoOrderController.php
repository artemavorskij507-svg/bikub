<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EcoOrderController extends Controller
{
    /**
     * Create eco disposal order.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',
            'address.street' => 'required|string|max:255',
            'address.city' => 'required|string|max:100',
            'address.postal_code' => 'required|string|max:10',
            'address.lat' => 'nullable|numeric|between:-90,90',
            'address.lng' => 'nullable|numeric|between:-180,180',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'scheduled_at' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create or find customer
            $customer = User::firstOrCreate(
                ['email' => $request->customer['email']],
                [
                    'name' => $request->customer['name'],
                    'phone' => $request->customer['phone'],
                    'password' => bcrypt(uniqid()),
                    'is_active' => true,
                ]
            );

            // Create address
            $address = Address::create([
                'street_address' => $request->address['street'],
                'city' => $request->address['city'],
                'postal_code' => $request->address['postal_code'],
                'latitude' => $request->address['lat'] ?? 68.4372,
                'longitude' => $request->address['lng'] ?? 17.4256,
                'formatted_address' => sprintf(
                    '%s, %s, %s',
                    $request->address['street'],
                    $request->address['postal_code'],
                    $request->address['city']
                ),
            ]);

            // Get eco service type
            $ecoService = ServiceType::where('category', 'eco')
                ->where('code', 'eco.pickup.general')
                ->first();

            if (! $ecoService) {
                // Fallback: create or use default eco service
                $ecoService = ServiceType::firstOrCreate(
                    ['code' => 'eco.pickup.general'],
                    [
                        'name' => 'Вывоз мусора и утилизация',
                        'slug' => 'eco-pickup-general',
                        'category' => 'eco',
                        'is_active' => true,
                    ]
                );
            }

            // Calculate total
            $basePrice = 499; // Base pickup fee
            $itemsTotal = collect($request->items)->sum(function ($item) {
                return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            });
            $totalAmount = $basePrice + $itemsTotal;

            // Create order
            $order = Order::create([
                'user_id' => $customer->id,
                'address_id' => $address->id,
                'service_type' => $ecoService->code ?? 'eco',
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
                'total_amount' => $totalAmount,
                'currency' => 'NOK',
                'metadata' => [
                    'service_type' => 'eco_disposal',
                    'items' => $request->items,
                    'base_price' => $basePrice,
                ],
            ]);

            // Create order items
            foreach ($request->items as $itemData) {
                $order->orderItems()->create([
                    'service_type_id' => $ecoService->id,
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['price'] ?? 0,
                    'total_price' => ($itemData['price'] ?? 0) * ($itemData['quantity'] ?? 1),
                    'currency' => 'NOK',
                    'metadata' => [
                        'item_id' => $itemData['id'] ?? null,
                        'category' => $itemData['category'] ?? null,
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $totalAmount,
                    'currency' => 'NOK',
                ],
                'message' => 'Eco order created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Eco order creation failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create eco order: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate eco disposal price.
     */
    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $basePrice = 499;
        $itemsTotal = collect($request->items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        });
        $total = $basePrice + $itemsTotal;

        // Calculate recycling percentage (simplified)
        $recyclingPercentage = min(95, 50 + (count($request->items) * 5));

        return response()->json([
            'success' => true,
            'data' => [
                'base_price' => $basePrice,
                'items_total' => round($itemsTotal, 2),
                'total' => round($total, 2),
                'currency' => 'NOK',
                'recycling_percentage' => $recyclingPercentage,
                'co2_saved_kg' => round(count($request->items) * 12.5, 1), // Estimated CO2 saved
            ],
        ]);
    }
}
