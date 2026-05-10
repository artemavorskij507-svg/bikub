<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Events\Delivery\OrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackingController extends Controller
{
    /**
     * Get tracking information for an order.
     */
    public function show(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
    {
        $this->authorizeTrackingView($request, $deliveryOrder);

        $deliveryOrder->loadMissing(['order', 'courier', 'orderable']);

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $deliveryOrder->order_id,
                'delivery_order_id' => $deliveryOrder->id,
                'type' => $deliveryOrder->type->value,
                'tracking_status' => $deliveryOrder->tracking_status->value,
                'eta' => $deliveryOrder->eta?->toIso8601String(),
                'courier_location' => $deliveryOrder->courier_location,
                'pickup_location' => $deliveryOrder->pickup_location,
                'delivery_location' => $deliveryOrder->delivery_location,
                'updated_at' => $deliveryOrder->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update tracking status.
     */
    public function updateStatus(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
    {
        if (! $this->canUpdate($request->user(), $deliveryOrder)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $request->validate([
            'tracking_status' => [
                'required',
                Rule::in(array_map(fn ($case) => $case->value, DeliveryTrackingStatus::cases())),
            ],
            'courier_location' => ['nullable', 'array'],
            'courier_location.lat' => ['required_with:courier_location', 'numeric'],
            'courier_location.lng' => ['required_with:courier_location', 'numeric'],
        ]);

        $deliveryOrder->tracking_status = DeliveryTrackingStatus::from($request->tracking_status);
        $deliveryOrder->courier_location = $request->courier_location ?? $deliveryOrder->courier_location;

        if ($deliveryOrder->tracking_status === DeliveryTrackingStatus::DELIVERED) {
            $deliveryOrder->actual_delivery_time = now();
        }

        $deliveryOrder->save();

        event(new OrderUpdated($deliveryOrder));

        return response()->json([
            'success' => true,
            'message' => 'Tracking status updated',
            'data' => [
                'tracking_status' => $deliveryOrder->tracking_status->value,
                'eta' => $deliveryOrder->eta?->toIso8601String(),
            ],
        ]);
    }

    protected function canUpdate($user, DeliveryOrder $deliveryOrder): bool
    {
        if (! $user) {
            return false;
        }

        if ($deliveryOrder->courier_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['admin', 'dispatcher']);
    }

    protected function authorizeTrackingView(Request $request, DeliveryOrder $deliveryOrder): void
    {
        $user = $request->user();

        if ($user && ($this->isOrderOwner($user, $deliveryOrder) || $this->canUpdate($user, $deliveryOrder))) {
            return;
        }

        $providedToken = $request->query('tracking_token') ?? $request->bearerToken();

        if ($providedToken && hash_equals($deliveryOrder->tracking_token, $providedToken)) {
            return;
        }

        abort(response()->json([
            'success' => false,
            'message' => 'Forbidden',
        ], 403));
    }

    protected function isOrderOwner($user, DeliveryOrder $deliveryOrder): bool
    {
        if (! $user || ! $deliveryOrder->order) {
            return false;
        }

        return $deliveryOrder->order->user_id === $user->id;
    }
}
