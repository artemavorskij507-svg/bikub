<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Models\ScheduleSlot;
use Illuminate\Support\Facades\DB;

class UpdateSlotUtilization
{
    public function handleTaskCreated(TaskCreated $event): void
    {
        if ($event->task->slot_id) {
            $this->recalculateSlot($event->task->slot_id);
        }
    }

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        if ($event->task->slot_id) {
            $this->recalculateSlot($event->task->slot_id);
        }
    }

    protected function recalculateSlot($slotId): void
    {
        $slot = ScheduleSlot::find($slotId);
        if (! $slot) {
            return;
        }

        $bookedCount = DB::table('tasks')
            ->where('slot_id', $slotId)
            ->whereIn('status', ['ready', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->count();

        $slot->reserved_count = $bookedCount;
        $slot->save();
    }
}
