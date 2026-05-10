<?php

namespace App\Domain\Roadside\Actions;

use App\Models\Operations\Assignment;
use App\Models\Operations\ServiceJob;

class ApplyEmergencyAcceptanceTimeoutAction
{
    public function execute(ServiceJob $job, Assignment $assignment, array $runtimeRules): Assignment
    {
        $isEmergency = (string) $job->service_domain === 'roadside'
            && (in_array((string) $job->priority, ['urgent', 'emergency'], true)
                || (bool) data_get($job->metadata, 'is_emergency', false));

        $timeoutSeconds = $isEmergency
            ? (int) data_get($runtimeRules, 'roadside.acceptance_timeout_seconds', 120)
            : 600;

        $assignment->update([
            'acceptance_timeout_seconds' => $timeoutSeconds,
            'acceptance_deadline_at' => now()->addSeconds(max(30, $timeoutSeconds)),
        ]);

        return $assignment->fresh();
    }
}

