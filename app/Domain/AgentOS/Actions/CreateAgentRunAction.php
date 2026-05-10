<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentRunRiskLevel;
use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Models\AgentRun;

class CreateAgentRunAction
{
    public function execute(array $payload): AgentRun
    {
        $riskLevel = (string) ($payload['risk_level'] ?? AgentRunRiskLevel::MEDIUM->value);
        $organizationId = $payload['organization_id'] ?? null;
        $tenantId = $payload['tenant_id'] ?? null;
        $idempotencyKey = $payload['idempotency_key'] ?? null;

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $activeStatuses = [
                AgentRunStatus::QUEUED->value,
                AgentRunStatus::PLANNING->value,
                AgentRunStatus::EXECUTING->value,
                AgentRunStatus::WAITING_DEPENDENCIES->value,
                AgentRunStatus::VALIDATION_FAILED->value,
                AgentRunStatus::NEEDS_REVISION->value,
                AgentRunStatus::READY_FOR_REVIEW->value,
                AgentRunStatus::APPROVED->value,
                AgentRunStatus::DEPLOY_READY->value,
                AgentRunStatus::DEPLOYING->value,
            ];

            $existing = AgentRun::query()
                ->where('organization_id', $organizationId)
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $idempotencyKey)
                ->whereIn('status', $activeStatuses)
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $requiresApproval = (bool) ($payload['requires_approval'] ?? false);
        $deploymentAllowed = (bool) ($payload['deployment_allowed'] ?? false);

        if (in_array($riskLevel, [AgentRunRiskLevel::HIGH->value, AgentRunRiskLevel::CRITICAL->value], true)) {
            $requiresApproval = true;
            $deploymentAllowed = false;
        }

        return AgentRun::query()->create([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'status' => $payload['status'] ?? AgentRunStatus::QUEUED->value,
            'risk_level' => $riskLevel,
            'requires_approval' => $requiresApproval,
            'deployment_allowed' => $deploymentAllowed,
            'idempotency_key' => $idempotencyKey,
            'goal' => $payload['goal'] ?? null,
            'terminal_reason' => $payload['terminal_reason'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'started_at' => $payload['started_at'] ?? null,
            'finished_at' => $payload['finished_at'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
            'updated_by' => $payload['updated_by'] ?? null,
        ]);
    }
}
