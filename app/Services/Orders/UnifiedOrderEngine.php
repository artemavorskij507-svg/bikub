<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use App\Services\Delivery\OrderFactory as DeliveryOrderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnifiedOrderEngine
{
    public function __construct(
        private readonly OrderScenarioRegistry $scenarios,
        private readonly OrderPricingCoordinator $pricing,
        private readonly ?DeliveryOrderFactory $deliveryOrderFactory = null,
    ) {}

    public function create(string $scenarioCode, ?User $user, array $payload): Order
    {
        $scenario = $this->scenarios->getEnabled($scenarioCode);
        $missing = $this->scenarios->validateRequiredFields($scenario, $payload);
        if ($missing !== []) {
            throw new \InvalidArgumentException('Missing required fields: '.implode(', ', $missing));
        }

        $price = $this->pricing->estimate($scenario, $payload);
        $initialStatus = ($scenario['payment_required'] ?? false)
            ? OrderStatus::PaymentPending->value
            : OrderStatus::Created->value;

        return DB::transaction(function () use ($scenario, $scenarioCode, $user, $payload, $price, $initialStatus): Order {
            if (($scenario['service_domain'] ?? null) === 'delivery' && $this->deliveryOrderFactory && $user) {
                $deliveryOrder = $this->deliveryOrderFactory->create(
                    $scenario['delivery_type'] ?? 'grocery',
                    $user,
                    $this->normalizeDeliveryPayload($payload),
                    ['total' => $price['total_amount']],
                );

                $order = $deliveryOrder->order()->firstOrFail();
                $order->forceFill([
                    'service_type' => $scenarioCode,
                    'status' => $initialStatus,
                    'total_amount' => $price['total_amount'],
                    'currency' => $price['currency'],
                    'payment_status' => 'pending',
                    'priority' => ! empty($payload['is_urgent']) ? 'urgent' : 'normal',
                    'metadata' => $this->mergeEngineMetadata((array) ($order->metadata ?? []), $scenario, $payload, $price),
                ])->save();

                $this->writeEvent($order, 'order_created', null, $initialStatus, $user->id, [
                    'scenario_key' => $scenarioCode,
                    'source' => 'checkout',
                ]);
                event(new OrderCreated($order));

                return $order->fresh(['deliveryOrder']);
            }

            $order = Order::create([
                'user_id' => $user?->id,
                'service_type' => $scenarioCode,
                'status' => $initialStatus,
                'priority' => ! empty($payload['is_urgent']) ? 'urgent' : 'normal',
                'location' => [
                    'pickup' => $payload['pickup_location'] ?? $payload['current_location'] ?? null,
                    'delivery' => $payload['delivery_location'] ?? null,
                ],
                'scheduled_at' => $payload['scheduled_at'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'total_amount' => $price['total_amount'],
                'currency' => $price['currency'],
                'payment_status' => 'pending',
                'metadata' => $this->mergeEngineMetadata([], $scenario, $payload, $price),
            ]);

            $this->writeEvent($order, 'order_created', null, $initialStatus, $user?->id, [
                'scenario_key' => $scenarioCode,
                'source' => 'checkout',
            ]);
            event(new OrderCreated($order));

            return $order;
        });
    }

    private function normalizeDeliveryPayload(array $payload): array
    {
        return [
            'pickup_location' => $payload['pickup_location'] ?? [
                'address' => $payload['pickup_address'] ?? 'Narvik partner pickup',
            ],
            'delivery_location' => $payload['delivery_location'] ?? [
                'address' => $payload['delivery_address'] ?? $payload['address'] ?? 'Customer address',
            ],
            'pickup_address' => $payload['pickup_address'] ?? null,
            'delivery_address' => $payload['delivery_address'] ?? $payload['address'] ?? null,
            'preferred_delivery_window' => $payload['delivery_window'] ?? $payload['slot'] ?? null,
            'store_id' => $payload['store_id'] ?? null,
            'restaurant_id' => $payload['restaurant_id'] ?? null,
            'items' => $payload['items'] ?? [],
            'notes' => $payload['notes'] ?? null,
            'is_urgent' => (bool) ($payload['is_urgent'] ?? false),
            'substitution_policy' => $payload['substitution_policy'] ?? 'strict',
        ];
    }

    private function mergeEngineMetadata(array $metadata, array $scenario, array $payload, array $price): array
    {
        $metadata['order_engine'] = [
            'scenario' => $scenario['code'] ?? $scenario['key'],
            'scenario_key' => $scenario['key'] ?? $scenario['code'] ?? null,
            'service_domain' => $scenario['service_domain'] ?? null,
            'worker_type' => $scenario['worker_type'] ?? null,
            'sla_minutes' => $scenario['sla_minutes'] ?? null,
            'checkout_payload' => $this->safePayload($payload),
            'pricing' => $price,
            'created_at' => now()->toIso8601String(),
        ];

        return $metadata;
    }

    private function safePayload(array $payload): array
    {
        unset($payload['password'], $payload['token'], $payload['api_key'], $payload['payment_method']);

        return $payload;
    }

    private function writeEvent(Order $order, string $type, ?string $fromStatus, ?string $toStatus, ?int $actorId, array $payload): void
    {
        if (! Schema::hasTable('order_events')) {
            return;
        }

        OrderEvent::create([
            'order_id' => $order->id,
            'actor_id' => $actorId,
            'actor_type' => $actorId ? 'user' : null,
            'event_type' => $type,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'payload' => $payload,
        ]);
    }
}
