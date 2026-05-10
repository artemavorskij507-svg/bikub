<?php

namespace App\Http\Controllers\Api\Courier;

use App\Enums\DeliveryTrackingStatus;
use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierDeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courier = $request->user();

        $orders = DeliveryOrder::query()
            ->where('courier_id', $courier->id)
            ->whereIn('tracking_status', [
                DeliveryTrackingStatus::ASSIGNED,
                DeliveryTrackingStatus::PICKED_UP,
                DeliveryTrackingStatus::IN_TRANSIT,
            ])
            ->with(['order.user'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn (DeliveryOrder $delivery) => $this->formatDelivery($delivery)),
        ]);
    }

    public function show(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
    {
        $courier = $request->user();

        if ($deliveryOrder->courier_id !== $courier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $deliveryOrder->load(['order.user']);

        return response()->json([
            'success' => true,
            'data' => $this->formatDelivery($deliveryOrder, true),
        ]);
    }

    protected function formatDelivery(DeliveryOrder $deliveryOrder, bool $withMeta = false): array
    {
        $payload = [
            'delivery_order_id' => $deliveryOrder->id,
            'order_id' => $deliveryOrder->order_id,
            'type' => $deliveryOrder->type->value,
            'tracking_status' => $deliveryOrder->tracking_status->value,
            'eta' => $deliveryOrder->eta?->toIso8601String(),
            'pickup_address' => $deliveryOrder->pickup_address,
            'delivery_address' => $deliveryOrder->delivery_address,
            'pickup_location' => $deliveryOrder->pickup_location,
            'delivery_location' => $deliveryOrder->delivery_location,
            'created_at' => $deliveryOrder->created_at->toIso8601String(),
        ];

        if ($withMeta) {
            $payload['estimated_distance_km'] = $deliveryOrder->estimated_distance_km;
            $payload['estimated_duration_minutes'] = $deliveryOrder->estimated_duration_minutes;
            $payload['metadata'] = $deliveryOrder->metadata;
        }

        return $payload;
    }
}
