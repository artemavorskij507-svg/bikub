<?php

namespace App\Services\Scheduling;

use App\Models\ScheduleSlot;
use App\Models\SlotReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SlotReservationService
{
    public function hold(string $orderId, string $slotId, int $qty = 1): SlotReservation
    {
        return DB::transaction(function () use ($orderId, $slotId, $qty) {
            $slot = ScheduleSlot::lockForUpdate()->findOrFail($slotId);
            $ttl = (int) ($slot->hold_ttl_sec ?? 900);

            if ($slot->oversell_policy === 'deny') {
                if ($slot->reserved_count + $qty > $slot->capacity_total) {
                    throw ValidationException::withMessages(['slot' => 'Нет доступной емкости']);
                }
            } elseif ($slot->oversell_policy === 'allow_soft') {
                $soft = $slot->capacity_soft_limit ?? $slot->capacity_total;
                if ($slot->reserved_count + $qty > $soft) {
                    throw ValidationException::withMessages(['slot' => 'Soft-лимит исчерпан']);
                }
            }

            $res = SlotReservation::create([
                'slot_id' => $slot->id,
                'order_id' => $orderId,
                'status' => 'hold',
                'quantity' => $qty,
                'expires_at' => now()->addSeconds($ttl),
            ]);
            $slot->increment('reserved_count', $qty);

            return $res;
        });
    }

    public function confirm(string $reservationId): SlotReservation
    {
        return DB::transaction(function () use ($reservationId) {
            $res = SlotReservation::lockForUpdate()->findOrFail($reservationId);
            if ($res->status !== 'hold') {
                throw ValidationException::withMessages(['reservation' => 'Не в статусе hold']);
            }
            $slot = ScheduleSlot::lockForUpdate()->findOrFail($res->slot_id);
            if ($slot->oversell_policy !== 'allow_hard') {
                if ($slot->confirmed_count + $res->quantity > $slot->capacity_total) {
                    throw ValidationException::withMessages(['slot' => 'Нет подтверждаемой емкости']);
                }
            }
            $res->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            $slot->increment('confirmed_count', $res->quantity);

            return $res;
        });
    }

    public function release(string $reservationId, string $newStatus = 'released'): void
    {
        DB::transaction(function () use ($reservationId, $newStatus) {
            $res = SlotReservation::lockForUpdate()->findOrFail($reservationId);
            $slot = ScheduleSlot::lockForUpdate()->findOrFail($res->slot_id);
            if ($res->status === 'hold') {
                $slot->decrement('reserved_count', $res->quantity);
            }
            if ($res->status === 'confirmed') {
                $slot->decrement('confirmed_count', $res->quantity);
            }
            $res->update(['status' => $newStatus]);
        });
    }

    public function expireHolds(): int
    {
        $expired = SlotReservation::where('status', 'hold')->whereNotNull('expires_at')->where('expires_at', '<=', now())->limit(1000)->get();
        foreach ($expired as $res) {
            $this->release($res->id, 'expired');
        }

        return $expired->count();
    }
}
