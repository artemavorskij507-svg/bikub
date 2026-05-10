<?php

namespace App\Services\Operations;

use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;

class DispatchScoringService
{
    public function evaluate(ServiceJob $job, Executor $executor): array
    {
        $ineligibilityReasons = $this->getIneligibilityReasons($job, $executor);
        if ($ineligibilityReasons !== []) {
            return [
                'eligible' => false,
                'score' => 0,
                'breakdown' => [],
                'reasons' => $ineligibilityReasons,
            ];
        }

        $distanceScore = $this->distanceScore($job, $executor);
        $loadScore = $this->loadScore($executor);
        $slaScore = $this->slaScore($job);
        $fairnessScore = $this->fairnessScore($executor);
        $costScore = $this->costScore($executor);

        $total = (
            ($distanceScore * 0.35) +
            ($loadScore * 0.20) +
            ($slaScore * 0.20) +
            ($fairnessScore * 0.15) +
            ($costScore * 0.10)
        );

        return [
            'eligible' => true,
            'score' => round($total, 4),
            'breakdown' => [
                'distance' => round($distanceScore, 4),
                'load' => round($loadScore, 4),
                'sla' => round($slaScore, 4),
                'fairness' => round($fairnessScore, 4),
                'cost' => round($costScore, 4),
            ],
            'reasons' => [],
        ];
    }

    private function getIneligibilityReasons(ServiceJob $job, Executor $executor): array
    {
        $reasons = [];

        if ($job->organization_id && $executor->organization_id && $job->organization_id !== $executor->organization_id) {
            $reasons[] = 'organization_mismatch';
        }

        if (! in_array($executor->status, ['available', 'idle', 'online'], true)) {
            $reasons[] = 'executor_not_available';
        }

        if (! $this->hasRequiredSkills($job, $executor)) {
            $reasons[] = 'missing_required_skills';
        }

        if (! $this->inShift($executor)) {
            $reasons[] = 'not_in_shift';
        }

        if (! $this->hasCapacity($job, $executor)) {
            $reasons[] = 'capacity_constraint';
        }

        if (! $this->withinTimeWindow($job)) {
            $reasons[] = 'time_window_violation';
        }

        return $reasons;
    }

    private function hasRequiredSkills(ServiceJob $job, Executor $executor): bool
    {
        $requiredSkills = collect($job->required_skills ?? [])->filter()->values();
        if ($requiredSkills->isEmpty()) {
            return true;
        }

        $executorSkills = $executor->skills->pluck('skill_code')->values();

        return $requiredSkills->every(fn ($skill) => $executorSkills->contains($skill));
    }

    private function inShift(Executor $executor): bool
    {
        if ($executor->shifts->isEmpty()) {
            return true;
        }

        $now = now();

        return $executor->shifts->contains(function ($shift) use ($now) {
            return $shift->is_available && $shift->starts_at <= $now && $shift->ends_at >= $now;
        });
    }

    private function hasCapacity(ServiceJob $job, Executor $executor): bool
    {
        $activeAssignments = $executor->assignments()->whereIn('status', ['assigned', 'accepted', 'arrived', 'started'])->count();

        if ($activeAssignments >= max(1, (int) $executor->max_concurrent_jobs)) {
            return false;
        }

        $required = $job->required_capacity ?? [];
        if ($required === [] || ! is_array($required)) {
            return true;
        }

        $available = $executor->capacity ?? [];
        foreach ($required as $key => $requiredValue) {
            if (! isset($available[$key]) || (float) $available[$key] < (float) $requiredValue) {
                return false;
            }
        }

        return true;
    }

    private function withinTimeWindow(ServiceJob $job): bool
    {
        if (! $job->time_window_end) {
            return true;
        }

        return now()->lte($job->time_window_end);
    }

    private function distanceScore(ServiceJob $job, Executor $executor): float
    {
        $lastLocation = $executor->locations()->latest('recorded_at')->first();
        if (! $lastLocation) {
            return 0.5;
        }

        $point = $job->service_point ?? $job->pickup_point ?? $job->dropoff_point ?? null;
        if (! is_array($point)) {
            return 0.5;
        }

        $lat = (float) ($point['lat'] ?? 0);
        $lng = (float) ($point['lng'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return 0.5;
        }

        $distance = sqrt((($lat - (float) $lastLocation->lat) ** 2) + (($lng - (float) $lastLocation->lng) ** 2));

        return max(0.1, 1 - min(1, $distance * 8));
    }

    private function loadScore(Executor $executor): float
    {
        $activeAssignments = $executor->assignments()->whereIn('status', ['assigned', 'accepted', 'arrived', 'started'])->count();
        $max = max(1, (int) $executor->max_concurrent_jobs);

        return max(0, 1 - ($activeAssignments / $max));
    }

    private function slaScore(ServiceJob $job): float
    {
        if (! $job->time_window_end) {
            return 0.7;
        }

        $minutesLeft = now()->diffInMinutes($job->time_window_end, false);
        if ($minutesLeft <= 0) {
            return 0;
        }
        if ($minutesLeft <= 15) {
            return 1;
        }
        if ($minutesLeft <= 45) {
            return 0.85;
        }

        return 0.7;
    }

    private function fairnessScore(Executor $executor): float
    {
        $completedToday = $executor->assignments()
            ->whereDate('completed_at', today())
            ->count();

        return max(0.2, 1 - min(1, $completedToday / 10));
    }

    private function costScore(Executor $executor): float
    {
        $cost = (float) data_get($executor->metadata, 'cost_per_job', 10);

        return max(0.1, 1 - min(1, $cost / 50));
    }
}

