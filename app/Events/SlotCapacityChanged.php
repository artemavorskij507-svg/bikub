<?php

namespace App\Events;

use App\Models\ScheduleSlot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlotCapacityChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ScheduleSlot $slot,
        public int $oldCapacity,
        public int $newCapacity
    ) {}
}
