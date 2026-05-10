<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\ClientConfirmationRequested;
use App\Events\OrderCompleted;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class OrderLifecycleService
{
    /**
     * @var array<string, string[]>
     */
    private array $transitions = [
        'draft' => ['created', 'cancelled'],
        'created' => ['payment_pending', 'confirmed', 'cancelled', 'failed'],
        'payment_pending' => ['payment_reserved', 'confirmed', 'cancelled', 'failed'],
        'payment_reserved' => ['confirmed', 'cancelled', 'failed'],
        'confirmed' => ['waiting_dispatch', 'cancelled', 'failed'],
        'waiting_dispatch' => ['assigned', 'cancelled', 'failed'],
        'assigned' => ['worker_accepted', 'waiting_dispatch', 'cancelled'],
        'worker_accepted' => ['worker_en_route', 'cancelled'],
        'worker_en_route' => ['at_pickup', 'cancelled'],
        'at_pickup' => ['picked_up', 'cancelled'],
        'picked_up' => ['in_progress', 'arrived', 'cancelled'],
        'in_progress' => ['arrived', 'completed', 'cancelled'],
        'arrived' => ['completed', 'cancelled'],
        'completed' => ['client_confirmed', 'disputed'],
        'client_confirmed' => ['paid_out', 'refunded', 'disputed'],
        'paid_out' => [],
        'cancelled' => ['refunded'],
        'refunded' => [],
        'disputed' => ['cancelled', 'refunded', 'completed'],
        'failed' => ['created', 'cancelled'],
    ];

    public function transition(Order $order, string $status, ?int $actorId = null, array $context = [], bool $override = false): Order
    {
        $status = OrderStatus::normalize($status);

        if (! in_array($status, OrderStatus::allAcceptedValues(), true)) {
            throw new InvalidArgumentException("Unsupported order status [{$status}].");
        }

        $from = (string) $order->status;
        if (! $override && ! $this->canTransition($from, $status)) {
            throw new InvalidArgumentException("Invalid order status transition [{$from} -> {$status}].");
        }

        if ($override && empty($context['override_reason'])) {
            throw new InvalidArgumentException('Admin override requires override_reason.');
        }

        $order->status = $status;

        if ($status === OrderStatus::Assigned->value && empty($order->assigned_at) && $order->offsetExists('assigned_at')) {
            $order->assigned_at = now();
        }

        if ($status === OrderStatus::InProgress->value && ! $order->started_at) {
            $order->started_at = now();
        }

        if ($status === OrderStatus::Completed->value && ! $order->completed_at) {
            $order->completed_at = now();
        }

        if ($status === OrderStatus::Cancelled->value && $order->offsetExists('cancelled_at')) {
            $order->cancelled_at = now();
        }

        $metadata = (array) ($order->metadata ?? []);
        $metadata['lifecycle'][] = [
            'from' => $from,
            'to' => $status,
            'actor_id' => $actorId,
            'context' => $context,
            'override' => $override,
            'changed_at' => now()->toIso8601String(),
        ];
        $order->metadata = $metadata;
        $order->save();

        $this->writeEvent($order, 'status_changed', $from, $status, $actorId, $context + ['override' => $override]);
        event(new OrderStatusChanged($order, $from, $status));
        if ($status === OrderStatus::Completed->value) {
            event(new OrderCompleted($order));
            event(new ClientConfirmationRequested($order));
        }

        Log::info('Order lifecycle transition', [
            'order_id' => $order->id,
            'from' => $from,
            'to' => $status,
            'actor_id' => $actorId,
            'override' => $override,
        ]);

        return $order->fresh();
    }

    public function availableNextStatuses(Order $order): array
    {
        return $this->transitions[$order->status] ?? [];
    }

    private function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->transitions[$from] ?? [], true);
    }

    private function writeEvent(Order $order, string $eventType, ?string $from, ?string $to, ?int $actorId, array $payload = []): void
    {
        if (! Schema::hasTable('order_events')) {
            return;
        }

        OrderEvent::create([
            'order_id' => $order->id,
            'actor_id' => $actorId,
            'actor_type' => $actorId ? 'user' : null,
            'event_type' => $eventType,
            'from_status' => $from,
            'to_status' => $to,
            'payload' => $payload,
        ]);
    }
}
