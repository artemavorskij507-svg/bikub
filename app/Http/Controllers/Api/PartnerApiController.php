<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\ServiceType;
use App\Models\WebhookSubscription;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PartnerApiController extends Controller
{
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|uuid|exists:service_types,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'sometimes|email|max:255',
            'delivery_address' => 'required|string|max:500',
            'delivery_latitude' => 'required|numeric|between:-90,90',
            'delivery_longitude' => 'required|numeric|between:-180,180',
            'scheduled_at' => 'sometimes|date|after:now',
            'items' => 'sometimes|array',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'sometimes|string|max:1000',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed',
                'message' => 'Invalid request data',
                'details' => $validator->errors(),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $serviceType = ServiceType::findOrFail($request->input('service_type_id'));
            $partner = $this->getPartnerFromToken();

            // Calculate pricing
            $pricingService = app(PricingService::class);
            $pricing = $pricingService->calculatePrice($serviceType, [
                'latitude' => $request->input('delivery_latitude'),
                'longitude' => $request->input('delivery_longitude'),
                'scheduled_at' => $request->input('scheduled_at'),
                'items' => $request->input('items', []),
            ]);

            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'partner_id' => $partner->id,
                'service_type_id' => $serviceType->id,
                'customer_name' => $request->input('customer_name'),
                'customer_phone' => $request->input('customer_phone'),
                'customer_email' => $request->input('customer_email'),
                'delivery_address' => $request->input('delivery_address'),
                'delivery_latitude' => $request->input('delivery_latitude'),
                'delivery_longitude' => $request->input('delivery_longitude'),
                'scheduled_at' => $request->input('scheduled_at'),
                'status' => 'pending',
                'total_amount' => $pricing['total_price'],
                'base_price' => $pricing['base_price'],
                'surge_multiplier' => $pricing['surge_multiplier'],
                'notes' => $request->input('notes'),
                'metadata' => array_merge($request->input('metadata', []), [
                    'created_via' => 'partner_api',
                    'pricing_calculation' => $pricing,
                ]),
            ]);

            // Create order items
            if ($request->has('items')) {
                foreach ($request->input('items') as $item) {
                    $order->items()->create([
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['quantity'] * $item['price'],
                    ]);
                }
            }

            DB::commit();

            // Trigger webhook
            $this->triggerWebhook('order.created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'estimated_delivery' => $pricing['estimated_delivery'],
                'created_at' => $order->created_at,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'order_creation_failed',
                'message' => 'Failed to create order',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrder(Request $request, string $orderId): JsonResponse
    {
        $partner = $this->getPartnerFromToken();

        $order = Order::where('id', $orderId)
            ->where('partner_id', $partner->id)
            ->with(['items', 'serviceType', 'tasks'])
            ->first();

        if (! $order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Order not found or access denied',
            ], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'service_type' => [
                'id' => $order->serviceType->id,
                'name' => $order->serviceType->name,
                'slug' => $order->serviceType->slug,
            ],
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
            'delivery' => [
                'address' => $order->delivery_address,
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
            ],
            'pricing' => [
                'total_amount' => $order->total_amount,
                'base_price' => $order->base_price,
                'surge_multiplier' => $order->surge_multiplier,
            ],
            'timing' => [
                'scheduled_at' => $order->scheduled_at,
                'estimated_delivery' => $order->estimated_delivery,
                'actual_delivery' => $order->actual_delivery,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                ];
            }),
            'tasks' => $order->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'type' => $task->type,
                    'status' => $task->status,
                    'assigned_to' => $task->assigned_to,
                    'estimated_completion' => $task->estimated_completion,
                ];
            }),
            'notes' => $order->notes,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ]);
    }

    public function getServices(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromToken();

        $services = ServiceType::where('is_active', true)
            ->where(function ($query) use ($partner) {
                $query->whereNull('partner_id')
                    ->orWhere('partner_id', $partner->id);
            })
            ->with(['pricingRules'])
            ->get();

        return response()->json([
            'services' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'description' => $service->description,
                    'category' => $service->category,
                    'base_price' => $service->base_price,
                    'pricing_rules' => $service->pricingRules->map(function ($rule) {
                        return [
                            'id' => $rule->id,
                            'name' => $rule->name,
                            'type' => $rule->type,
                            'value' => $rule->value,
                            'conditions' => $rule->conditions,
                        ];
                    }),
                    'is_available' => $service->is_available,
                    'estimated_duration' => $service->estimated_duration,
                ];
            }),
        ]);
    }

    public function getZones(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromToken();

        $zones = GeoZone::where('is_active', true)
            ->where(function ($query) use ($partner) {
                $query->whereNull('partner_id')
                    ->orWhere('partner_id', $partner->id);
            })
            ->get();

        return response()->json([
            'zones' => $zones->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'slug' => $zone->slug,
                    'description' => $zone->description,
                    'center_latitude' => $zone->center_latitude,
                    'center_longitude' => $zone->center_longitude,
                    'radius_km' => $zone->radius_km,
                    'delivery_fee' => $zone->delivery_fee,
                    'is_available' => $zone->is_available,
                ];
            }),
        ]);
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'zone_id' => 'sometimes|uuid|exists:geo_zones,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed',
                'message' => 'Invalid request data',
                'details' => $validator->errors(),
            ], 400);
        }

        $partner = $this->getPartnerFromToken();
        $date = $request->input('date');
        $zoneId = $request->input('zone_id');

        $slots = ScheduleSlot::where('date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($partner, $zoneId) {
                $query->where('partner_id', $partner->id);
                if ($zoneId) {
                    $query->where('zone_id', $zoneId);
                }
            })
            ->with(['zone'])
            ->get();

        return response()->json([
            'date' => $date,
            'slots' => $slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'capacity' => $slot->capacity,
                    'booked' => $slot->booked,
                    'available' => $slot->getAvailableCapacity(),
                    'zone' => [
                        'id' => $slot->zone->id,
                        'name' => $slot->zone->name,
                    ],
                    'is_available' => $slot->isAvailable(),
                ];
            }),
        ]);
    }

    public function cancelOrder(Request $request, string $orderId): JsonResponse
    {
        $partner = $this->getPartnerFromToken();

        $order = Order::where('id', $orderId)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Order not found or access denied',
            ], 404);
        }

        if (! in_array($order->status, ['pending', 'confirmed', 'assigned'])) {
            return response()->json([
                'error' => 'invalid_status',
                'message' => 'Order cannot be cancelled in current status',
            ], 400);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason', 'Cancelled by partner'),
        ]);

        // Trigger webhook
        $this->triggerWebhook('order.cancelled', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'cancelled_at' => $order->cancelled_at,
            'reason' => $order->cancellation_reason,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'cancelled_at' => $order->cancelled_at,
            'message' => 'Order cancelled successfully',
        ]);
    }

    public function getOrderStatus(Request $request, string $orderId): JsonResponse
    {
        $partner = $this->getPartnerFromToken();

        $order = Order::where('id', $orderId)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $order) {
            return response()->json([
                'error' => 'order_not_found',
                'message' => 'Order not found or access denied',
            ], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'estimated_delivery' => $order->estimated_delivery,
            'actual_delivery' => $order->actual_delivery,
            'updated_at' => $order->updated_at,
        ]);
    }

    private function getPartnerFromToken()
    {
        $token = request()->bearerToken();
        $accessToken = \App\Models\OauthAccessToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->with('client.partner')
            ->first();

        if (! $accessToken) {
            abort(401, 'Invalid or expired token');
        }

        return $accessToken->client->partner;
    }

    private function generateOrderNumber(): string
    {
        return 'GLF-'.strtoupper(uniqid());
    }

    private function triggerWebhook(string $event, array $payload): void
    {
        $partner = $this->getPartnerFromToken();

        $subscriptions = WebhookSubscription::where('partner_id', $partner->id)
            ->where('active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->isSubscribedTo($event)) {
                $subscription->trigger($event, $payload);
            }
        }
    }
}
