<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\Task;
use Illuminate\Support\Carbon;

class TaskGenerator
{
    public function generateForOrder(Order $order): array
    {
        $created = [];

        try {
            $baseWindowStart = $order->scheduled_at
                ? ($order->scheduled_at instanceof \Carbon\Carbon
                    ? $order->scheduled_at
                    : Carbon::parse($order->scheduled_at))
                : now()->addHour();
        } catch (\Exception $e) {
            \Log::warning('Failed to parse scheduled_at in TaskGenerator', [
                'order_id' => $order->id,
                'scheduled_at' => $order->scheduled_at,
                'error' => $e->getMessage(),
            ]);
            $baseWindowStart = now()->addHour();
        }
        $baseWindowEnd = (clone $baseWindowStart)->addHours(2);

        // Basic window/slot validation: pick any slot intersecting [start,end]
        $slot = ScheduleSlot::query()
            ->where('start_time', '<=', $baseWindowEnd)
            ->where('end_time', '>=', $baseWindowStart)
            ->orderBy('start_time')
            ->first();

        $defaultTasks = [
            ['type' => 'pickup', 'sequence_index' => 1],
            ['type' => 'dropoff', 'sequence_index' => 2],
        ];

        foreach ($defaultTasks as $t) {
            $created[] = Task::create([
                'order_id' => $order->id,
                'type' => $t['type'],
                'status' => 'queued',
                'priority' => 'normal',
                'sequence_index' => $t['sequence_index'],
                'window_start' => $baseWindowStart,
                'window_end' => $baseWindowEnd,
                'slot_id' => $slot?->id,
                'expected_duration_min' => 20,
                'currency' => $order->currency ?? 'NOK',
                'meta' => [
                    'generated' => true,
                ],
            ]);
        }

        return $created;
    }
}
