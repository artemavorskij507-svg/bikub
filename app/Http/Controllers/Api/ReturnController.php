<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReturnController extends Controller
{
    /**
     * Create a return request
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|uuid|exists:orders,id',
            'type' => 'required|in:return_full,return_partial,replacement',
            'reason' => 'required|string',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|uuid|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        // Check if user owns the order
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order',
            ], 403);
        }

        // Check if order is eligible for return
        if (! $this->isOrderEligibleForReturn($order)) {
            return response()->json([
                'success' => false,
                'message' => 'Order is not eligible for return',
            ], 400);
        }

        // Calculate restocking fee
        $restockingFee = $this->calculateRestockingFee($order, $request->type);

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'type' => $request->type,
            'reason' => $request->reason,
            'status' => 'pending',
            'items' => $request->items,
            'restocking_fee' => $restockingFee,
            'notes' => $request->notes,
            'meta' => [
                'created_by' => Auth::id(),
                'created_at' => now(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'return' => $orderReturn,
            'estimated_refund' => $orderReturn->calculateRefundAmount(),
            'message' => 'Return request created successfully',
        ]);
    }

    /**
     * Get user's returns
     */
    public function getUserReturns(): JsonResponse
    {
        $returns = OrderReturn::whereHas('order', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with(['order', 'refunds'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'returns' => $returns,
        ]);
    }

    /**
     * Get return details
     */
    public function getReturn(string $id): JsonResponse
    {
        $orderReturn = OrderReturn::with(['order', 'refunds', 'returnItems'])
            ->findOrFail($id);

        // Check if user owns the order
        if ($orderReturn->order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'return' => $orderReturn,
        ]);
    }

    /**
     * Cancel return request
     */
    public function cancel(string $id): JsonResponse
    {
        $orderReturn = OrderReturn::findOrFail($id);

        // Check if user owns the order
        if ($orderReturn->order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return',
            ], 403);
        }

        if ($orderReturn->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel return request in current status',
            ], 400);
        }

        $orderReturn->update([
            'status' => 'cancelled',
            'meta' => array_merge($orderReturn->meta ?? [], [
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Return request cancelled successfully',
        ]);
    }

    public function createReturn(Request $request): JsonResponse
    {
        return $this->create($request);
    }

    public function getReturns(Request $request, string $id): JsonResponse
    {
        // Keep route shape /orders/{id}/returns compatible.
        if ($request->boolean('single')) {
            return $this->getReturn($id);
        }

        return $this->getUserReturns();
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $orderReturn = OrderReturn::findOrFail($id);

        // Basic guarded update to avoid 500 on route invocation.
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,processing,completed,cancelled'],
        ]);

        $orderReturn->status = $data['status'];
        $orderReturn->meta = array_merge($orderReturn->meta ?? [], [
            'status_updated_by' => Auth::id(),
            'status_updated_at' => now(),
        ]);
        $orderReturn->save();

        return response()->json([
            'success' => true,
            'return' => $orderReturn,
        ]);
    }

    /**
     * Check if order is eligible for return
     */
    private function isOrderEligibleForReturn(Order $order): bool
    {
        // Check if order is completed
        if ($order->status !== 'completed') {
            return false;
        }

        // Check return window based on service type
        $returnWindow = $this->getReturnWindow($order);
        $daysSinceCompletion = $order->completed_at->diffInDays(now());

        return $daysSinceCompletion <= $returnWindow;
    }

    /**
     * Get return window in days based on service type
     */
    private function getReturnWindow(Order $order): int
    {
        $serviceType = $order->items->first()?->serviceType?->category;

        return match ($serviceType) {
            'food' => 1, // 24 hours for food
            'market' => 14, // 14 days for market items
            'care', 'eco', 'tow' => 7, // 7 days for services
            default => 7
        };
    }

    /**
     * Calculate restocking fee
     */
    private function calculateRestockingFee(Order $order, string $type): float
    {
        if ($type === 'replacement') {
            return 0; // No fee for replacement
        }

        $serviceType = $order->items->first()?->serviceType?->category;

        return match ($serviceType) {
            'food' => 0, // No fee for food returns
            'market' => $order->total_amount * 0.1, // 10% for market items
            default => $order->total_amount * 0.05 // 5% for services
        };
    }
}
