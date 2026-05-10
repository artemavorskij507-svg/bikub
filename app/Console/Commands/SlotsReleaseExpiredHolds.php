<?php

namespace App\Console\Commands;

use App\Models\ScheduleSlot;
use Illuminate\Console\Command;

class SlotsReleaseExpiredHolds extends Command
{
    protected $signature = 'slots:release-expired';

    protected $description = 'Снимает истёкшие hold’ы со слотов';

    public function handle(): int
    {
        ScheduleSlot::query()->with('orders')->whereHas('orders', function ($q) {
            $q->where('order_schedule_slot.reservation_status', 'hold')
                ->whereNotNull('order_schedule_slot.expires_at')
                ->where('order_schedule_slot.expires_at', '<', now());
        })->chunkById(200, function ($slots) {
            foreach ($slots as $slot) {
                foreach ($slot->orders as $o) {
                    $pv = $o->pivot;
                    if ($pv->reservation_status === 'hold' && $pv->expires_at && $pv->expires_at < now()) {
                        $slot->decrement('capacity_reserved');
                        $slot->orders()->detach($o->id);
                    }
                }
                event(new \App\Events\SlotCapacityUpdated($slot->id));
            }
        });
        $this->info('Expired holds released');

        return self::SUCCESS;
    }
}
