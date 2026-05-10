<?php

namespace App\Domain\Dispatch\Actions;

use App\Models\Operations\ServiceJob;

class ResolveRuntimeDispatchRuleSetAction
{
    public function __construct(
        private readonly ResolveDispatchRuleSetAction $resolveDispatchRuleSetAction,
        private readonly LoadDispatchRuleValuesAction $loadDispatchRuleValuesAction,
        private readonly ApplyDispatchRuleOverridesAction $applyDispatchRuleOverridesAction,
    ) {}

    public function execute(ServiceJob $job): array
    {
        $ruleSet = $this->resolveDispatchRuleSetAction->execute($job);
        $defaults = [
            'rule_set' => class_basename($ruleSet),
            'weights' => method_exists($ruleSet, 'weights') ? $ruleSet->weights() : [],
            'modifiers' => [
                'window_high_risk_penalty' => -12,
                'window_medium_risk_penalty' => -5,
                'emergency_boost' => 20,
                'domain_priority_boost' => 0,
                'load_penalty_scale' => 1.0,
                'idle_executor_boost' => 4,
                'load_1_penalty' => 0,
                'load_2_penalty' => -6,
                'load_3_plus_penalty' => -12,
            ],
            'roadside' => [
                'preemption_enabled' => true,
                'acceptance_timeout_seconds' => 120,
            ],
            'moving' => [
                'default_required_team_size' => 2,
            ],
        ];

        $overrides = $this->loadDispatchRuleValuesAction->execute(
            organizationId: $job->organization_id,
            tenantId: $job->tenant_id,
            serviceDomain: (string) $job->service_domain,
            jobKind: $job->job_kind ?: $job->job_type,
        );

        return $this->applyDispatchRuleOverridesAction->execute($defaults, $overrides);
    }
}

