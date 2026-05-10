<?php

namespace App\Listeners;

use App\Domain\Operations\Events\ServiceJobStatusChanged;
use App\Models\Order;

class SyncOrderStatusFromServiceJob
{
    public function handle(ServiceJobStatusChanged $event): void
    {
        $job = $event->job;

        $order = null;
        if ($job->source_type === 'order' && $job->source_id) {
            $order = Order::query()->find($job->source_id);
        }
        if (! $order && $job->order_id) {
            $order = Order::query()->find($job->order_id);
        }
        if (! $order) {
            return;
        }

        $mappedStatus = match ($job->status) {
            'pending_dispatch' => 'pending',
            'assigned' => 'assigned',
            'en_route' => 'on_the_way',
            'arrived' => 'arrived',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'failed' => 'failed',
            default => null,
        };

        if (! $mappedStatus || $order->status === $mappedStatus) {
            return;
        }

        $order->update(['status' => $mappedStatus]);
    }
}

