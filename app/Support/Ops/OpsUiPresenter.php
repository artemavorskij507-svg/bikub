<?php

namespace App\Support\Ops;

use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;

class OpsUiPresenter
{
    public static function exceptionType(OperationException $exception): string
    {
        $type = (string) ($exception->type ?: $exception->exception_type ?: 'unknown');

        return str_replace('_', ' ', $type);
    }

    public static function exceptionTypeValue(OperationException $exception): string
    {
        return (string) ($exception->type ?: $exception->exception_type ?: 'unknown');
    }

    public static function riskScore(ServiceJob $job, int $exceptionsCount, string $slaState): int
    {
        $score = match (JobStatusPresenter::normalize($job->status)) {
            'pending_dispatch' => 25,
            'assigned' => 35,
            'en_route' => 45,
            'arrived' => 50,
            'in_progress' => 55,
            'completed' => 0,
            'cancelled', 'failed' => 70,
            default => 20,
        };

        $score += match ($slaState) {
            'warning' => 25,
            'breached' => 45,
            default => 0,
        };

        $score += min($exceptionsCount * 10, 30);

        return max(0, min(100, $score));
    }

    public static function isAtRisk(ServiceJob $job, string $slaState, int $exceptionsCount): bool
    {
        if ($slaState !== 'ok' || $exceptionsCount > 0) {
            return true;
        }

        return in_array(JobStatusPresenter::normalize($job->status), ['pending_dispatch', 'assigned', 'en_route'], true)
            && $job->updated_at && $job->updated_at->lt(now()->subMinutes(20));
    }

    public static function etaForJob(ServiceJob $job): ?string
    {
        $eta = $job->promised_eta_at ?? optional($job->currentAssignment)->eta_at;

        return $eta ? $eta->format('H:i') : null;
    }
}

