<?php

namespace App\Domain\Dispatch\Actions;

use App\Models\Operations\ServiceJob;

class ComputeDomainPriorityModifierAction
{
    public function execute(ServiceJob $job, array $runtimeRules): array
    {
        $priority = (string) $job->priority;
        $modifiers = (array) data_get($runtimeRules, 'modifiers', []);

        $base = (float) ($modifiers['domain_priority_boost'] ?? 0);
        $reason = 'domain_default';

        if ((string) $job->service_domain === 'roadside' && in_array($priority, ['urgent', 'emergency'], true)) {
            $base += (float) ($modifiers['emergency_boost'] ?? 20);
            $reason = 'roadside_emergency_priority';
        } elseif ((string) $job->service_domain === 'delivery' && in_array($priority, ['express', 'high', 'urgent'], true)) {
            $base += 12;
            $reason = 'delivery_express_priority';
        } elseif ((string) $job->service_domain === 'handyman' && (bool) data_get($job->metadata, 'warranty_followup', false)) {
            $base += 10;
            $reason = 'handyman_warranty_followup';
        } elseif ((string) $job->service_domain === 'moving' && in_array($priority, ['premium', 'high', 'urgent'], true)) {
            $base += 8;
            $reason = 'moving_premium_priority';
        }

        return [
            'modifier' => round($base, 2),
            'reason' => $reason,
        ];
    }
}

