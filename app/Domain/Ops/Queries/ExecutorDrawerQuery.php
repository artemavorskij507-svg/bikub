<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Operations\Models\Executor;
use App\Support\Ops\ExecutorPresenter;
use Illuminate\Support\Facades\Redis;

class ExecutorDrawerQuery
{
    public function execute(string $organizationId, int $executorId): array
    {
        $executor = Executor::query()
            ->where('organization_id', (string) $organizationId)
            ->with(['assignments' => fn ($q) => $q->latest('id')->limit(20)])
            ->findOrFail($executorId);

        $activeAssignment = Assignment::query()
            ->where('executor_id', $executor->id)
            ->whereIn('status', ['proposed', 'offered', 'accepted', 'active'])
            ->latest('id')
            ->with('serviceJob:id,status,service_domain,job_kind,job_type')
            ->first();

        $lastLocationRaw = Redis::get("executor:{$executor->id}:last_location");
        $lastLocation = $lastLocationRaw ? json_decode($lastLocationRaw, true) : null;
        $lastSeen = Redis::get("executor:{$executor->id}:last_seen_at");

        return [
            'entity_updated_at' => optional($executor->updated_at)->toIso8601String(),
            'drawer_version' => optional($executor->updated_at)?->format('Y-m-d H:i:s.u'),
            'executor' => [
                'id' => $executor->id,
                'display_name' => $executor->display_name ?: $executor->name,
                'status' => ExecutorPresenter::normalize($executor->status),
                'status_label' => ExecutorPresenter::label($executor->status),
                'vehicle_type' => $executor->vehicle_type,
                'skills' => $executor->skills,
                'equipment' => $executor->equipment,
                'last_seen_at' => $lastSeen,
                'stale' => $lastSeen ? now()->subMinutes(5)->gte(\Carbon\Carbon::parse($lastSeen)) : true,
            ],
            'active_assignment' => $activeAssignment ? [
                'id' => $activeAssignment->id,
                'status' => $activeAssignment->status,
                'service_job_id' => $activeAssignment->service_job_id,
                'eta' => optional($activeAssignment->eta_at)->toIso8601String(),
            ] : null,
            'active_job' => $activeAssignment?->serviceJob ? [
                'id' => $activeAssignment->serviceJob->id,
                'status' => $activeAssignment->serviceJob->status,
                'domain' => $activeAssignment->serviceJob->service_domain,
                'kind' => $activeAssignment->serviceJob->job_kind ?: $activeAssignment->serviceJob->job_type,
            ] : null,
            'last_location' => $lastLocation,
        ];
    }
}
