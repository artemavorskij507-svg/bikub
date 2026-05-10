<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Exceptions\Models\OperationException;
use App\Support\Ops\ExceptionPresenter;

class ExceptionDrawerQuery
{
    public function execute(string $organizationId, int $exceptionId): array
    {
        $exception = OperationException::query()
            ->where('organization_id', (string) $organizationId)
            ->with([
                'serviceJob:id,status,service_domain,job_kind,job_type,executor_id,assignment_id,promised_eta_at',
                'serviceJob.executor:id,name,display_name,status',
            ])
            ->findOrFail($exceptionId);

        return [
            'entity_updated_at' => optional($exception->updated_at)->toIso8601String(),
            'drawer_version' => optional($exception->updated_at)?->format('Y-m-d H:i:s.u'),
            'exception' => [
                'id' => $exception->id,
                'type' => ExceptionPresenter::value($exception),
                'type_label' => ExceptionPresenter::label(ExceptionPresenter::value($exception)),
                'severity' => $exception->severity,
                'status' => $exception->status,
                'service_job_id' => $exception->service_job_id,
                'assignment_id' => $exception->assignment_id,
                'executor_id' => $exception->executor_id,
                'detected_at' => optional($exception->detected_at)?->toIso8601String(),
                'owner_user_id' => $exception->owner_user_id,
                'payload' => $exception->payload,
                'context' => [
                    'summary' => $exception->summary,
                    'root_cause' => $exception->root_cause,
                    'resolution_code' => $exception->resolution_code,
                ],
                'updated_at' => optional($exception->updated_at)->toIso8601String(),
            ],
            'linked_job' => $exception->serviceJob ? [
                'id' => $exception->serviceJob->id,
                'status' => $exception->serviceJob->status,
                'domain' => $exception->serviceJob->service_domain,
                'kind' => $exception->serviceJob->job_kind ?: $exception->serviceJob->job_type,
                'executor_id' => $exception->serviceJob->executor_id,
                'assignment_id' => $exception->serviceJob->assignment_id,
                'eta' => optional($exception->serviceJob->promised_eta_at)->toIso8601String(),
            ] : null,
            'linked_executor' => $exception->serviceJob?->executor ? [
                'id' => $exception->serviceJob->executor->id,
                'display_name' => $exception->serviceJob->executor->display_name ?: $exception->serviceJob->executor->name,
                'status' => $exception->serviceJob->executor->status,
            ] : null,
        ];
    }
}
