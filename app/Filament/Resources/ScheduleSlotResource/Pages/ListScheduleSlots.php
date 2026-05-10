<?php

namespace App\Filament\Resources\ScheduleSlotResource\Pages;

use App\Filament\Resources\ScheduleSlotResource;
use App\Models\ScheduleSlot;
use Filament\Resources\Pages\ListRecords;

class ListScheduleSlots extends ListRecords
{
    protected static string $resource = ScheduleSlotResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedLocalDemoScheduleSlotsIfEmpty();
    }

    protected function seedLocalDemoScheduleSlotsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (ScheduleSlot::query()->exists()) {
            return;
        }

        // Current schema uses start_at/end_at and capacity_* fields.
        $baseDate = now()->startOfDay();

        $slots = [
            ['MORNING', 'Morning Window', 8, 11],
            ['NOON', 'Noon Window', 11, 14],
            ['AFTERNOON', 'Afternoon Window', 14, 17],
            ['EVENING', 'Evening Window', 17, 20],
        ];

        foreach ($slots as $index => [$code, $name, $fromHour, $toHour]) {
            $startAt = $baseDate->copy()->setHour($fromHour);
            $endAt = $baseDate->copy()->setHour($toHour);

            ScheduleSlot::query()->create([
                'code' => $code,
                'name' => $name,
                'kind' => 'delivery',
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'open',
                'max_orders' => 20,
                'capacity_total' => 20,
                'capacity_reserved' => $index,
                'capacity_confirmed' => $index,
                'courier_required' => 1,
                'courier_assigned' => 0,
                'capacity_soft_limit' => 20,
                'reserved_count' => $index,
                'confirmed_count' => $index,
                'oversell_policy' => 'deny',
                'hold_ttl_sec' => 900,
                'meta' => ['source' => 'local_demo_seed'],
            ]);
        }
    }
}
