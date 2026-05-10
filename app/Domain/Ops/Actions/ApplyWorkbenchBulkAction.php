<?php

namespace App\Domain\Ops\Actions;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Operations\Models\ServiceJob;

class ApplyWorkbenchBulkAction
{
    public function execute(string|int $organizationId, string $action, array $ids, int $actorUserId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return match ($action) {
            'exceptions_bulk_acknowledge' => $this->bulkAckExceptions((string) $organizationId, $ids, $actorUserId),
            'exceptions_bulk_resolve' => $this->bulkResolveExceptions((string) $organizationId, $ids, $actorUserId),
            'jobs_bulk_reassign_request' => $this->bulkCreateReassignRequest((string) $organizationId, $ids, $actorUserId),
            'jobs_bulk_assign_dispatcher_queue' => $this->bulkAssignDispatcherQueue((string) $organizationId, $ids, $actorUserId),
            default => [
                'action' => $action,
                'updated_count' => 0,
                'message' => 'Unsupported bulk action',
            ],
        };
    }

    private function bulkAckExceptions(string $organizationId, array $exceptionIds, int $actorUserId): array
    {
        $updated = OperationException::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $exceptionIds)
            ->whereIn('status', ['open', 'investigating', 'mitigated'])
            ->update([
                'status' => 'acknowledged',
                'owner_user_id' => $actorUserId,
                'acknowledged_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'action' => 'exceptions_bulk_acknowledge',
            'updated_count' => $updated,
            'message' => 'Exceptions acknowledged',
        ];
    }

    private function bulkResolveExceptions(string $organizationId, array $exceptionIds, int $actorUserId): array
    {
        $updated = OperationException::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $exceptionIds)
            ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
            ->update([
                'status' => 'resolved',
                'owner_user_id' => $actorUserId,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'action' => 'exceptions_bulk_resolve',
            'updated_count' => $updated,
            'message' => 'Exceptions resolved',
        ];
    }

    private function bulkCreateReassignRequest(string $organizationId, array $jobIds, int $actorUserId): array
    {
        $jobs = ServiceJob::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $jobIds)
            ->get(['id', 'executor_id', 'service_domain']);

        $created = 0;
        foreach ($jobs as $job) {
            OperationException::query()->create([
                'organization_id' => $organizationId,
                'service_job_id' => $job->id,
                'executor_id' => $job->executor_id,
                'type' => 'reassign_requested',
                'severity' => 'medium',
                'status' => 'open',
                'detected_at' => now(),
                'owner_user_id' => $actorUserId,
                'payload' => [
                    'source' => 'workbench_bulk_action',
                    'requested_by' => $actorUserId,
                    'service_domain' => $job->service_domain,
                ],
            ]);
            $created++;
        }

        return [
            'action' => 'jobs_bulk_reassign_request',
            'updated_count' => $created,
            'message' => 'Reassign requests created',
        ];
    }

    private function bulkAssignDispatcherQueue(string $organizationId, array $jobIds, int $actorUserId): array
    {
        $jobs = ServiceJob::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $jobIds)
            ->get(['id', 'metadata']);

        $updated = 0;
        foreach ($jobs as $job) {
            $metadata = (array) ($job->metadata ?? []);
            $metadata['dispatcher_queue'] = [
                'assigned' => true,
                'assigned_by' => $actorUserId,
                'assigned_at' => now()->toIso8601String(),
            ];
            $job->metadata = $metadata;
            $job->save();
            $updated++;
        }

        return [
            'action' => 'jobs_bulk_assign_dispatcher_queue',
            'updated_count' => $updated,
            'message' => 'Jobs assigned to dispatcher queue',
        ];
    }
}
