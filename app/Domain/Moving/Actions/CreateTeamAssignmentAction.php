<?php

namespace App\Domain\Moving\Actions;

use App\Domain\Moving\Models\TeamAssignment;
use App\Models\Operations\ServiceJob;

class CreateTeamAssignmentAction
{
    public function execute(
        ServiceJob $job,
        array $memberExecutorIds,
        ?int $teamLeadExecutorId = null,
        ?int $teamEtaSeconds = null,
        array $memberEtas = [],
    ): TeamAssignment
    {
        return TeamAssignment::query()->create([
            'organization_id' => $job->organization_id,
            'tenant_id' => $job->tenant_id,
            'service_job_id' => $job->id,
            'team_lead_executor_id' => $teamLeadExecutorId ?: ($memberExecutorIds[0] ?? null),
            'member_executor_ids_json' => array_values(array_unique($memberExecutorIds)),
            'status' => 'proposed',
            'eta_at' => now()->addSeconds(max(300, (int) ($teamEtaSeconds ?? (35 * 60)))),
            'metadata' => [
                'member_etas' => array_values($memberEtas),
            ],
        ]);
    }
}
