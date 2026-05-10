<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class FoodOrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'items' => 'required|array',
            'items.*. => 'required|numeric|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string|max:500',
        ]);

        try {
            $restaurant = Restaurant::findOrFail($request->restaurant_id);
            $total = collect($request->items);
            $sum = 0;
            foreach ($request->items as $item) {
                $sum += $item['price'] * $item['quantity'];
            }

            $order = new Order();
            $order->restaurant_id = $request->restaurant_id;
            $order->restaurant_name = $restaurant->name;
            $order->items = json_encode($request->items);
            $order->total_amount = $total;
            $order->customer_name = $request->customer_name;
            $order->customer_phone = $request->customer_phone;
            $order->delivery_address = $request->delivery_address;
            $order->status = 'pending';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $order->id,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            Log::error('Food order failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
            ], 422);
        }
    }
}
