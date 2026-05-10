<?php

namespace App\Domain\Roadside\Actions;

use App\Models\Operations\ServiceJob;

class ApplyEmergencyPriorityOverrideAction
{
    public function execute(ServiceJob $job, float $score, array $runtimeRules = []): float
    {
        $isEmergency = in_array((string) $job->priority, ['urgent', 'emergency'], true)
            || (bool) data_get($job->metadata, 'is_emergency', false);

        if ((string) $job->service_domain !== 'roadside' || ! $isEmergency) {
            return $score;
        }

        $boost = (float) data_get($runtimeRules, 'modifiers.emergency_boost', 20);

        return min(100.0, $score + $boost);
    }
}
