<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Apply coupon to order
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|uuid|exists:orders,id',
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code',
            ], 404);
        }

        if (! $coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not valid or has expired',
            ], 400);
        }

        if (! $coupon->isApplicableToOrder($order)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not applicable to this order',
            ], 400);
        }

        $discountAmount = $coupon->calculateDiscount($order);

        // Update order with coupon
        $order->update([
            'coupon_id' => $coupon->id,
            'coupon_discount' => $discountAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $order->total_amount - $discountAmount,
        ]);

        // Increment coupon usage
        $coupon->incrementUsage();

        return response()->json([
            'success' => true,
            'discount_amount' => $discountAmount,
            'new_total' => $order->total_amount,
            'coupon' => $coupon,
            'message' => 'Coupon applied successfully',
        ]);
    }

    /**
     * Remove coupon from order
     */
    public function remove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|uuid|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if (! $order->coupon_id) {
            return response()->json([
                'success' => false,
                'message' => 'No coupon applied to this order',
            ], 400);
        }

        $coupon = Coupon::find($order->coupon_id);
        $discountAmount = $order->coupon_discount;

        // Restore original total
        $order->update([
            'coupon_id' => null,
            'coupon_discount' => 0,
            'discount_amount' => $order->discount_amount - $discountAmount,
            'total_amount' => $order->total_amount + $discountAmount,
        ]);

        // Decrement coupon usage
        if ($coupon) {
            $coupon->decrement('used');
        }

        return response()->json([
            'success' => true,
            'new_total' => $order->total_amount,
            'message' => 'Coupon removed successfully',
        ]);
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code',
            ], 404);
        }

        if (! $coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not valid or has expired',
            ], 400);
        }

        // Create a temporary order to check applicability
        $tempOrder = new Order([
            'total_amount' => $request->order_amount,
            'items' => [], // Empty for validation
        ]);

        if (! $coupon->isApplicableToOrder($tempOrder)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not applicable to this order amount',
            ], 400);
        }

        $discountAmount = $coupon->calculateDiscount($tempOrder);

        return response()->json([
            'success' => true,
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'message' => 'Coupon is valid',
        ]);
    }

    /**
     * Get available coupons
     */
    public function getAvailable(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereRaw('used < max_uses');
            })
            ->select(['code', 'name', 'type', 'value', 'minimum_order_amount', 'valid_to'])
            ->get();

        return response()->json([
            'success' => true,
            'coupons' => $coupons,
        ]);
    }
}
