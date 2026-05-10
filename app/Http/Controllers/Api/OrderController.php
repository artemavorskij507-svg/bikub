<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPaid;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderPricingService;
use App\Services\Orders\OrderLifecycleService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'assignedUser', 'orderItems']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
            'count' => $orders->count(),
            'message' => 'Orders retrieved successfully',
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'status' => 'sometimes|in:'.implode(',', OrderStatus::allAcceptedValues()),
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'items' => 'required|array|min:1',
            'items.*.service_type_id' => 'required|exists:service_types,id',
            'items.*.quantity' => 'sometimes|integer|min:1',
            'items.*.name' => 'sometimes|string',
            'location' => 'sometimes|array',
            'location.lat' => 'sometimes|numeric',
            'location.lng' => 'sometimes|numeric',
            'location.address' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'estimated_time' => 'sometimes|integer',
            'notes' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate pricing using OrderPricingService
            $pricingService = new OrderPricingService;
            $pricing = $pricingService->calculateOrderPrice([
                'items' => $request->items,
                'location' => $request->location,
            ]);

            // Calculate estimated delivery time
            $estimatedTime = $pricingService->calculateEstimatedTime($request->location);

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $request->user_id,
                'status' => $request->status ?? 'pending',
                'priority' => $request->priority ?? 'normal',
                'location' => $request->location,
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes ?? null,
                'total_amount' => $pricing['total_amount'],
                'currency' => $pricing['currency'],
                'payment_status' => 'pending',
                'metadata' => [
                    'estimated_time_minutes' => $estimatedTime,
                    'geo_zone' => $request->location ? $this->findGeoZone($request->location) : null,
                ],
            ]);

            // Create order items
            foreach ($pricing['items'] as $item) {
                $serviceType = \App\Models\ServiceType::find($item['service_type_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'service_type_id' => $item['service_type_id'],
                    'pricing_rule_id' => $item['pricing_rule_id'],
                    'name' => $item['name'] ?? $serviceType->name,
                    'description' => $serviceType->description ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'currency' => $pricing['currency'],
                ]);
            }

            // Send email notification
            try {
                Mail::to($order->user->email)->send(new \App\Mail\OrderCreatedMail($order));
            } catch (\Exception $e) {
                // Log error but don't fail order creation
                \Log::warning('Failed to send order email: '.$e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['orderItems', 'user']),
                'message' => 'Order created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $order = Order::with(['user', 'assignedUser', 'orderItems.serviceType'])->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully',
        ]);
    }

    /**
     * Find geo zone for a location.
     */
    private function findGeoZone(?array $location): ?string
    {
        if (! $location || ! isset($location['lat']) || ! isset($location['lng'])) {
            return null;
        }

        $geoZone = \App\Models\GeoZone::active()
            ->get()
            ->first(function ($zone) use ($location) {
                return $zone->containsPoint($location['lat'], $location['lng']);
            });

        return $geoZone ? $geoZone->slug : null;
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, string $id, OrderLifecycleService $lifecycle)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:'.implode(',', OrderStatus::allAcceptedValues()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order = $lifecycle->transition($order, $request->status, $request->user()?->id, [
            'source' => 'api.orders.update_status',
        ]);

        return response()->json([
            'success' => true,
            'data' => $order->load(['orderItems', 'user']),
            'message' => 'Order status updated successfully',
        ]);
    }

    /**
     * Create payment intent for an order.
     */
    public function createPaymentIntent(Request $request, string $id)
    {
        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));
        $idempotencyCacheKey = null;
        $idempotencyRequestHash = null;

        if ($idempotencyKey !== '') {
            $idempotencyRequestHash = hash('sha256', json_encode([
                'order_id' => (string) $id,
                'action' => 'create_payment_intent',
            ], JSON_UNESCAPED_SLASHES));

            $idempotencyCacheKey = "order_payment_intent:idempotency:{$id}:{$idempotencyKey}";
            $processing = [
                'state' => 'processing',
                'request_hash' => $idempotencyRequestHash,
                'updated_at' => now()->toIso8601String(),
            ];

            if (! Cache::add($idempotencyCacheKey, $processing, now()->addMinutes(30))) {
                $existing = Cache::get($idempotencyCacheKey);

                if (is_array($existing) && ($existing['request_hash'] ?? null) !== $idempotencyRequestHash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Idempotency key is already used with different payload.',
                    ], 409);
                }

                if (is_array($existing) && ($existing['state'] ?? null) === 'completed') {
                    return response()->json(
                        (array) ($existing['response'] ?? ['success' => true]),
                        (int) ($existing['status'] ?? 200)
                    );
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Request is already being processed.',
                ], 409);
            }
        }

        $order = Order::find($id);

        if (! $order) {
            if ($idempotencyCacheKey !== null) {
                Cache::forget($idempotencyCacheKey);
            }
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if ($order->payment_status === 'paid') {
            $responsePayload = [
                'success' => false,
                'message' => 'Order already paid',
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 400,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload, 400);
        }

        $paymentService = new StripePaymentService;

        if (! $paymentService->isConfigured()) {
            $responsePayload = [
                'success' => false,
                'message' => 'Payment gateway not configured',
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 500,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload, 500);
        }

        $user = $order->user;
        $result = $paymentService->createPaymentIntent($order, [
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ]);

        if (! $result['success']) {
            $responsePayload = [
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $result['error'] ?? null,
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 500,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload, 500);
        }

        // Update order with payment intent ID
        $order->update([
            'payment_method' => 'stripe',
            'metadata' => array_merge($order->metadata ?? [], [
                'payment_intent_id' => $result['payment_intent_id'],
            ]),
        ]);

        $responsePayload = [
            'success' => true,
            'data' => [
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
            ],
            'message' => 'Payment intent created successfully',
        ];

        if ($idempotencyCacheKey !== null) {
            Cache::put($idempotencyCacheKey, [
                'state' => 'completed',
                'request_hash' => $idempotencyRequestHash,
                'status' => 200,
                'response' => $responsePayload,
            ], now()->addDay());
        }

        return response()->json($responsePayload);
    }

    /**
     * Confirm payment for an order.
     */
    public function confirmPayment(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'sometimes|string',
            'return_url' => 'sometimes|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));
        $idempotencyCacheKey = null;
        $idempotencyRequestHash = null;

        if ($idempotencyKey !== '') {
            $idempotencyRequestHash = hash('sha256', json_encode([
                'order_id' => (string) $id,
                'action' => 'confirm_payment',
                'payment_intent_id' => (string) $request->payment_intent_id,
                'payment_method_id' => (string) ($request->payment_method_id ?? ''),
                'return_url' => (string) ($request->return_url ?? ''),
            ], JSON_UNESCAPED_SLASHES));

            $idempotencyCacheKey = "order_payment_confirm:idempotency:{$id}:{$idempotencyKey}";
            $processing = [
                'state' => 'processing',
                'request_hash' => $idempotencyRequestHash,
                'updated_at' => now()->toIso8601String(),
            ];

            if (! Cache::add($idempotencyCacheKey, $processing, now()->addMinutes(30))) {
                $existing = Cache::get($idempotencyCacheKey);

                if (is_array($existing) && ($existing['request_hash'] ?? null) !== $idempotencyRequestHash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Idempotency key is already used with different payload.',
                    ], 409);
                }

                if (is_array($existing) && ($existing['state'] ?? null) === 'completed') {
                    return response()->json(
                        (array) ($existing['response'] ?? ['success' => true]),
                        (int) ($existing['status'] ?? 200)
                    );
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Request is already being processed.',
                ], 409);
            }
        }

        $order = Order::find($id);

        if (! $order) {
            if ($idempotencyCacheKey !== null) {
                Cache::forget($idempotencyCacheKey);
            }

            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $paymentService = new StripePaymentService;
        $status = $paymentService->getPaymentStatus($request->payment_intent_id);

        if (! ($status['success'] ?? false)) {
            $responsePayload = [
                'success' => false,
                'message' => 'Unable to retrieve payment intent status',
                'error' => $status['error'] ?? null,
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 422,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload, 422);
        }

        if (($status['status'] ?? null) === 'succeeded') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);

            OrderPaid::dispatch($order);

            $responsePayload = [
                'success' => true,
                'data' => $order->load(['orderItems', 'user']),
                'message' => 'Payment already confirmed',
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 200,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload);
        }

        $confirmOptions = [];
        if ($request->filled('payment_method_id')) {
            $confirmOptions['payment_method'] = (string) $request->payment_method_id;
        }
        if ($request->filled('return_url')) {
            $confirmOptions['return_url'] = (string) $request->return_url;
        }

        if ($confirmOptions === []) {
            $responsePayload = [
                'success' => false,
                'message' => 'Payment requires client-side confirmation or additional confirmation parameters.',
                'data' => [
                    'status' => $status['status'] ?? null,
                    'payment_intent_id' => $request->payment_intent_id,
                    'requires_action' => true,
                ],
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 422,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload, 422);
        }

        $result = $paymentService->confirmPaymentIntent($request->payment_intent_id, $confirmOptions);

        if ($result['success'] && $result['status'] === 'succeeded') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);

            // Dispatch OrderPaid event to trigger task generation
            OrderPaid::dispatch($order);

            $responsePayload = [
                'success' => true,
                'data' => $order->load(['orderItems', 'user']),
                'message' => 'Payment confirmed successfully',
            ];

            if ($idempotencyCacheKey !== null) {
                Cache::put($idempotencyCacheKey, [
                    'state' => 'completed',
                    'request_hash' => $idempotencyRequestHash,
                    'status' => 200,
                    'response' => $responsePayload,
                ], now()->addDay());
            }

            return response()->json($responsePayload);
        }

        $responsePayload = [
            'success' => false,
            'message' => 'Payment confirmation failed',
            'error' => $result['error'] ?? null,
            'data' => [
                'status' => $result['status'] ?? null,
                'payment_intent_id' => $request->payment_intent_id,
            ],
        ];

        if ($idempotencyCacheKey !== null) {
            Cache::put($idempotencyCacheKey, [
                'state' => 'completed',
                'request_hash' => $idempotencyRequestHash,
                'status' => 422,
                'response' => $responsePayload,
            ], now()->addDay());
        }

        return response()->json($responsePayload, 422);
    }
}
