<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\Task;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedLocalDemoTasksIfEmpty();
    }

    protected function seedLocalDemoTasksIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Task::query()->exists()) {
            return;
        }

        $orders = Order::query()->orderBy('id')->limit(6)->get();

        if ($orders->isEmpty()) {
            return;
        }

        $zoneIds = GeoZone::query()->pluck('id')->filter()->values();
        $statuses = ['queued', 'assigned', 'in_progress', 'completed'];
        $types = ['pickup', 'delivery', 'inspection'];
        $priorities = ['low', 'normal', 'high'];

        foreach ($orders as $index => $order) {
            $windowStart = now()->addHours($index + 1);
            $windowEnd = (clone $windowStart)->addMinutes(90);

            Task::query()->create([
                'order_id' => $order->id,
                'type' => $types[$index % count($types)],
                'status' => $statuses[$index % count($statuses)],
                'priority' => $priorities[$index % count($priorities)],
                'zone_id' => $zoneIds->isNotEmpty() ? $zoneIds[$index % $zoneIds->count()] : null,
                'address_text' => 'Demo task address #'.($index + 1),
                'expected_duration_min' => 30 + ($index * 10),
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
                'proof_required' => $index % 2 === 0,
                'instructions' => 'Auto-generated local demo task.',
                'requirements' => [
                    'skills' => ['demo'],
                    'vehicle' => 'car',
                ],
                'meta' => [
                    'source' => 'local_demo_seed',
                ],
            ]);
        }
    }
}
