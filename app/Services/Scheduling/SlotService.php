<?php

namespace App\Services\Scheduling;

use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SlotService
{
    public function createSlot(array $data): ScheduleSlot
    {
        $this->assertNoOverlap($data);

        return ScheduleSlot::create($data);
    }

    public function updateSlot(ScheduleSlot $slot, array $data): ScheduleSlot
    {
        $merged = array_merge($slot->toArray(), $data);
        $this->assertNoOverlap($merged, $slot->id);
        $slot->update($data);

        return $slot;
    }

    protected function assertNoOverlap(array $data, ?string $excludeId = null): void
    {
        // SQLite-safe overlap validation
        $exists = ScheduleSlot::query()
            ->where('zone_id', $data['zone_id'] ?? null)
            ->where('kind', $data['kind'] ?? 'delivery')
            ->whereIn('status', ['open', 'maintenance'])
            ->when($excludeId, fn ($q) => $q->where('id', '<>', $excludeId))
            ->where(function ($q) use ($data) {
                $q->where('end_at', '>', $data['start_at'])
                    ->where('start_at', '<', $data['end_at']);
            })
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['slot' => 'Пересечение слотов по зоне/типу/времени.']);
        }
    }

    public function generateDay(string $orgId, string $zoneId, string $date, string $kind, int $fromHour, int $toHour, int $stepMin, int $capacity, string $policy = 'deny'): int
    {
        $count = 0;
        DB::transaction(function () use (&$count, $orgId, $zoneId, $date, $kind, $fromHour, $toHour, $stepMin, $capacity, $policy) {
            $start = Carbon::parse($date)->setTime($fromHour, 0);
            $end = Carbon::parse($date)->setTime($toHour, 0);
            for ($t = $start->copy(); $t->lt($end); $t->addMinutes($stepMin)) {
                $slot = [
                    'org_id' => $orgId,
                    'zone_id' => $zoneId,
                    'kind' => $kind,
                    'start_at' => $t->copy(),
                    'end_at' => $t->copy()->addMinutes($stepMin),
                    'status' => 'open',
                    'capacity_total' => $capacity,
                    'oversell_policy' => $policy,
                ];
                $this->assertNoOverlap($slot);
                ScheduleSlot::firstOrCreate([
                    'zone_id' => $zoneId,
                    'kind' => $kind,
                    'start_at' => $slot['start_at'],
                    'end_at' => $slot['end_at'],
                ], $slot);
                $count++;
            }
        });

        return $count;
    }
}
