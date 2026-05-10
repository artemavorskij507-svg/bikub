<?php

namespace App\Listeners;

use App\Events\Operations\JobStatusChanged;

class OrderStatusSyncFromServiceJobListener
{
    public function handle(JobStatusChanged $event): void
    {
        $job = $event->job->fresh(['order']);
        if (! $job->order) {
            return;
        }

        $targetOrderStatus = match ($job->status) {
            'assigned' => 'assigned',
            'en_route' => 'on_the_way',
            'arrived', 'in_progress' => 'in_progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            default => null,
        };

        if (! $targetOrderStatus) {
            return;
        }

        $job->order->update([
            'status' => $targetOrderStatus,
        ]);
    }
}

