<?php

namespace App\Domain\Roadside\Actions;

use App\Models\Operations\Assignment;
use App\Models\Operations\ServiceJob;

class FindPreemptibleAssignmentsAction
{
    public function execute(ServiceJob $emergencyJob, int $executorId, int $limit = 5)
    {
        return Assignment::query()
            ->where('organization_id', $emergencyJob->organization_id)
            ->where('executor_id', $executorId)
            ->whereIn('status', ['proposed', 'accepted'])
            ->whereHas('serviceJob', function ($q) use ($emergencyJob): void {
                $q->where('id', '!=', $emergencyJob->id)
                    ->whereNotIn('priority', ['urgent', 'emergency']);
            })
            ->with('serviceJob:id,organization_id,priority,status,service_domain')
            ->orderByRaw("CASE WHEN status='proposed' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();
    }
}

