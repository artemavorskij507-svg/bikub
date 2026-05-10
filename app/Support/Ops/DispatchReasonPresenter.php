<?php

namespace App\Support\Ops;

class DispatchReasonPresenter
{
    public static function label(?string $reason): string
    {
        $reason = (string) $reason;
        if ($reason === '') {
            return 'Unknown reason';
        }

        if (str_starts_with($reason, 'missing_equipment:')) {
            $equipment = trim(substr($reason, strlen('missing_equipment:')));

            return $equipment !== '' ? "Missing equipment: {$equipment}" : 'Missing required equipment';
        }

        if (str_starts_with($reason, 'missing_required_equipment:')) {
            $equipment = trim(substr($reason, strlen('missing_required_equipment:')));

            return $equipment !== '' ? "Missing equipment: {$equipment}" : 'Missing required equipment';
        }

        if (str_starts_with($reason, 'missing_skill:')) {
            $skill = trim(substr($reason, strlen('missing_skill:')));

            return $skill !== '' ? "Missing skill: {$skill}" : 'Missing required skill';
        }

        if (str_starts_with($reason, 'missing_required_skills:')) {
            $skill = trim(substr($reason, strlen('missing_required_skills:')));

            return $skill !== '' ? "Missing skill: {$skill}" : 'Missing required skill';
        }

        return match ($reason) {
            'out_of_shift', 'after_shift_end' => 'After shift end',
            'before_shift_start' => 'Before shift start',
            'executor_on_break' => 'Executor on break',
            'time_window_miss' => 'Misses promised window',
            'capacity_mismatch' => 'Capacity mismatch',
            'missing_required_equipment' => 'Missing required equipment',
            'missing_required_skills' => 'Missing required skill',
            'missing_roadside_capability' => 'Missing roadside capability',
            'no_executor_found' => 'No executor found',
            default => ucfirst(str_replace('_', ' ', $reason)),
        };
    }
}

