<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\Schema;

class CheckExecutorShiftEligibilityAction
{
    public function execute(ServiceJob $job, Executor $executor, int $etaSeconds = 0): array
    {
        $now = now();
        $etaAt = $etaSeconds > 0 ? now()->addSeconds($etaSeconds) : null;
        $details = [];

        $shiftQuery = $executor->shifts()->where(function ($q) use ($now): void {
            if (Schema::hasColumn('executor_shifts', 'shift_date')) {
                $q->orWhere('shift_date', $now->toDateString());
            }
            if (Schema::hasColumn('executor_shifts', 'day_of_week')) {
                $q->orWhere('day_of_week', (int) $now->dayOfWeekIso);
            }
        });

        if (Schema::hasColumn('executor_shifts', 'is_available')) {
            $shiftQuery->where('is_available', true);
        }
        if (Schema::hasColumn('executor_shifts', 'is_active')) {
            $shiftQuery->where('is_active', true);
        }

        $shift = $shiftQuery->latest('id')->first();
        if (! $shift) {
            return [
                'eligible' => false,
                'reason' => 'out_of_shift',
                'details' => ['now' => $now->toIso8601String()],
            ];
        }

        $shiftStartsAt = $shift->starts_at ?? ($shift->start_time ? $now->copy()->setTimeFromTimeString($shift->start_time) : null);
        $shiftEndsAt = $shift->ends_at ?? ($shift->end_time ? $now->copy()->setTimeFromTimeString($shift->end_time) : null);

        if ($shiftStartsAt && $now->lt($shiftStartsAt)) {
            return [
                'eligible' => false,
                'reason' => 'before_shift_start',
                'details' => ['shift_starts_at' => $shiftStartsAt->toIso8601String()],
            ];
        }

        if ($shiftEndsAt && $now->gt($shiftEndsAt)) {
            return [
                'eligible' => false,
                'reason' => 'after_shift_end',
                'details' => ['shift_ends_at' => $shiftEndsAt->toIso8601String()],
            ];
        }

        $activeBreak = ExecutorBreak::query()
            ->where('executor_id', $executor->id)
            ->where('break_start_at', '<=', $now)
            ->where('break_end_at', '>=', $now)
            ->latest('id')
            ->first();

        if ($activeBreak) {
            return [
                'eligible' => false,
                'reason' => 'on_break',
                'details' => [
                    'break_start_at' => optional($activeBreak->break_start_at)->toIso8601String(),
                    'break_end_at' => optional($activeBreak->break_end_at)->toIso8601String(),
                ],
            ];
        }

        if ($etaAt && $shiftEndsAt && $etaAt->gt($shiftEndsAt)) {
            $details['eta_exceeds_shift'] = true;
            $details['eta_at'] = $etaAt->toIso8601String();
            $details['shift_ends_at'] = $shiftEndsAt->toIso8601String();
        }

        return [
            'eligible' => true,
            'reason' => null,
            'details' => $details,
        ];
    }
}

