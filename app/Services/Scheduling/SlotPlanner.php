<?php

namespace App\Services\Scheduling;

use App\Models\Order;
use App\Models\ScheduleSlot;

class SlotPlanner
{
    public function suggest(Order $order, int $limit = 5)
    {
        $zoneId = $order->zone_id;
        $serviceTypeId = $order->service_type_id;
        $need = collect($order->requirements ?? []);

        return ScheduleSlot::query()
            ->active()
            ->when($zoneId, fn ($q) => $q->where('zone_id', $zoneId))
            ->where(function ($q) use ($serviceTypeId) {
                $q->whereNull('service_type_id')
                    ->orWhere('service_type_id', $serviceTypeId);
            })
            ->where('start_at', '>=', now())
            ->whereRaw('(capacity_total - coalesce(capacity_reserved,0) - coalesce(capacity_confirmed,0)) > 0')
            ->orderBy('start_at')
            ->get()
            ->filter(function ($slot) use ($need) {
                $features = collect($slot->features ?? []);

                return $need->every(fn ($f) => $features->contains($f));
            })
            ->take($limit)
            ->values();
    }

    public function hold(ScheduleSlot $slot, Order $order, int $ttlMin = 30): bool
    {
        if ($slot->isFull() || $slot->status === 'closed') {
            return false;
        }

        $slot->increment('capacity_reserved');
        $slot->orders()->syncWithoutDetaching([
            $order->id => [
                'reservation_status' => 'hold',
                'expires_at' => now()->addMinutes($ttlMin),
            ],
        ]);

        event(new \App\Events\SlotCapacityUpdated($slot->id));

        return true;
    }

    public function confirm(ScheduleSlot $slot, Order $order): void
    {
        $slot->decrement('capacity_reserved');
        $slot->increment('capacity_confirmed');
        $slot->orders()->updateExistingPivot($order->id, [
            'reservation_status' => 'confirmed',
            'expires_at' => null,
        ]);
        event(new \App\Events\SlotCapacityUpdated($slot->id));
    }

    public function release(ScheduleSlot $slot, Order $order): void
    {
        $pivot = $slot->orders()->where('orders.id', $order->id)->first()?->pivot;
        if (! $pivot) {
            return;
        }
        if ($pivot->reservation_status === 'hold') {
            $slot->decrement('capacity_reserved');
        }
        if ($pivot->reservation_status === 'confirmed') {
            $slot->decrement('capacity_confirmed');
        }
        $slot->orders()->detach($order->id);
        event(new \App\Events\SlotCapacityUpdated($slot->id));
    }
}
