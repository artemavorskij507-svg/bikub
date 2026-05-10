<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderAssigned;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderAssignmentService
{
    public function assign(Order $order, User $worker, ?int $actorId = null, array $context = []): Order
    {
        $metadata = (array) ($order->metadata ?? []);
        $metadata['assignment'][] = [
            'worker_id' => $worker->id,
            'actor_id' => $actorId,
            'context' => $context,
            'assigned_at' => now()->toIso8601String(),
        ];

        $order->forceFill([
            'assigned_to' => $worker->id,
            'status' => OrderStatus::Assigned->value,
            'metadata' => $metadata,
        ])->save();

        Log::info('Order assigned', [
            'order_id' => $order->id,
            'worker_id' => $worker->id,
            'actor_id' => $actorId,
        ]);
        event(new OrderAssigned($order->fresh()));

        return $order->fresh();
    }

    public function unassign(Order $order, ?int $actorId = null, array $context = []): Order
    {
        $metadata = (array) ($order->metadata ?? []);
        $metadata['unassignment'][] = [
            'previous_worker_id' => $order->assigned_to,
            'actor_id' => $actorId,
            'context' => $context,
            'unassigned_at' => now()->toIso8601String(),
        ];

        $order->forceFill([
            'assigned_to' => null,
            'status' => OrderStatus::WaitingDispatch->value,
            'metadata' => $metadata,
        ])->save();

        Log::info('Order unassigned', [
            'order_id' => $order->id,
            'actor_id' => $actorId,
        ]);

        return $order->fresh();
    }
}
