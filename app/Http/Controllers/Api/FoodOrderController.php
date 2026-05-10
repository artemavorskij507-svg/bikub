<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class FoodOrderController extends Controller
{
    /**
     * Create a new food order
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.name' => 'required|string',
                'items.*.price' => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
                'delivery_address' => 'required|string',
                'phone' => 'required|string',
                'payment_method' => 'required|in:card,cash,wallet',
                'special_notes' => 'nullable|string',
            ]);
            
            // Calculate total
            $total = collect($validated['items'])->sum(function($item) {
                return $item['price'] * $item['quantity'];
            });
            
            // Create order
            $order = [
                'id' => 'ORDER-' . Carbon::now()->format('YmdHis') . '-' . rand(1000, 9999),
                'items' => $validated['items'],
                'total' => $total,
                'delivery_address' => $validated['delivery_address'],
                'phone' => $validated['phone'],
                'payment_method' => $validated['payment_method'],
                'special_notes' => $validated['special_notes'] ?? '',
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
                'estimated_delivery' => now()->addMinutes(35)->toIso8601String(),
            ];
            
            // Save to database
            Log::info('Food order created', ['order' => $order]);
            
            // Publish to Redis for real-time updates
            Redis::publish('glf-mat:new-order', json_encode($order));
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ создан!',
                'order' => $order,
                'delivery_time' => '30-45 минут',
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Food order error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заказа',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get order status
     */
    public function getStatus($orderId)
    {
        try {
            // Simulate order status
            $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'on-delivery', 'delivered'];
            
            return response()->json([
                'order_id' => $orderId,
                'status' => $statuses[array_rand($statuses)],
                'progress' => rand(20, 100),
                'estimated_arrival' => now()->addMinutes(rand(15, 40))->toIso8601String(),
                'driver_info' => [
                    'name' => 'Иван',
                    'phone' => '+47 900 12 345',
                    'vehicle' => 'Белый Ford Focus',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Order status error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статуса',
            ], 422);
        }
    }
    
    /**
     * Get menu items
     */
    public function getMenu()
    {
        $menu = [
            'soups' => [
                ['id' => 1, 'name' => 'Борщ Украинский', 'price' => 85, 'emoji' => '🥘'],
                ['id' => 2, 'name' => 'Шурпа Азербайджанская', 'price' => 95, 'emoji' => '🍲'],
            ],
            'mains' => [
                ['id' => 3, 'name' => 'Плов Азербайджанский', 'price' => 155, 'emoji' => '🍛'],
                ['id' => 4, 'name' => 'Люля-Кебаб', 'price' => 135, 'emoji' => '🍖'],
            ],
        ];
        
        return response()->json($menu);
    }
    
    /**
     * Apply promo code
     */
    public function applyPromo(Request $request)
    {
        $code = $request->input('code');
        
        $promoCodes = [
            'WELCOME10' => ['discount' => 10, 'type' => 'percent'],
            'FIRST50' => ['discount' => 50, 'type' => 'fixed'],
            'NARVIK20' => ['discount' => 20, 'type' => 'percent'],
        ];
        
        if (isset($promoCodes[$code])) {
            return response()->json([
                'success' => true,
                'code' => $code,
                'discount' => $promoCodes[$code]['discount'],
                'type' => $promoCodes[$code]['type'],
                'message' => 'Промокод применён!',
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Неверный промокод',
        ], 422);
    }
}
