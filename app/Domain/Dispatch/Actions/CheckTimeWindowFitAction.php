<?php

namespace App\Domain\Dispatch\Actions;

use App\Models\Operations\ServiceJob;

class CheckTimeWindowFitAction
{
    public function execute(ServiceJob $job, int $etaSeconds): array
    {
        $etaAt = now()->addSeconds(max(0, $etaSeconds));
        $windowStart = $job->time_window_start;
        $windowEnd = $job->time_window_end ?: $job->promised_eta_at;

        if (! $windowEnd) {
            return [
                'fits' => true,
                'risk' => 'low',
                'lateness_seconds' => 0,
            ];
        }

        if ($windowStart && $etaAt->lt($windowStart)) {
            return [
                'fits' => true,
                'risk' => 'medium',
                'lateness_seconds' => 0,
            ];
        }

        if ($etaAt->lte($windowEnd)) {
            $remaining = $windowEnd->diffInSeconds($etaAt, false);

            return [
                'fits' => true,
                'risk' => $remaining <= 600 ? 'high' : ($remaining <= 1800 ? 'medium' : 'low'),
                'lateness_seconds' => 0,
            ];
        }

        return [
            'fits' => false,
            'risk' => 'high',
            'lateness_seconds' => $windowEnd->diffInSeconds($etaAt),
        ];
    }
}

