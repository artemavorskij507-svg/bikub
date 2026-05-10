<?php

namespace App\Domain\Ops\Queries;

use App\Models\Operations\OperationException;
use App\Support\Ops\ExceptionPresenter;
use Illuminate\Database\Eloquent\Builder;

class OperationExceptionsTableQuery
{
    public function builder(array $filters = [], ?string $organizationId = null): Builder
    {
        $organizationScope = $this->resolveOrganizationScope($organizationId);

        $query = OperationException::query()
            ->with(['serviceJob:id,service_domain,status,executor_id', 'assignment:id,service_job_id,executor_id']);

        if ($organizationScope !== null) {
            $query->where('organization_id', $organizationScope);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['type'])) {
            $type = (string) $filters['type'];
            $query->where(function (Builder $q) use ($type): void {
                $q->where('type', $type)->orWhere('exception_type', $type);
            });
        }

        if (! empty($filters['domain'])) {
            $query->whereHas('serviceJob', function (Builder $q) use ($filters): void {
                $q->where('service_domain', $filters['domain']);
            });
        }

        return $query->orderByDesc('detected_at');
    }

    private function resolveOrganizationScope(?string $organizationId): ?string
    {
        if ($organizationId !== null && $organizationId !== '') {
            return $organizationId;
        }

        $user = auth()->user();
        $fromUser = (string) ($user?->organization_id ?? '');
        if ($fromUser !== '') {
            return $fromUser;
        }

        $fromDefault = (string) ($user?->default_org_id ?? '');
        return $fromDefault !== '' ? $fromDefault : null;
    }

    public function mapRow(OperationException $exception): array
    {
        $payload = is_array($exception->payload) ? $exception->payload : [];

        return [
            'id' => $exception->id,
            'type' => ExceptionPresenter::value($exception),
            'type_label' => ExceptionPresenter::label(ExceptionPresenter::value($exception)),
            'severity' => (string) $exception->severity,
            'status' => (string) $exception->status,
            'job_id' => $exception->service_job_id,
            'executor_id' => $exception->executor_id,
            'detected_at' => optional($exception->detected_at)->toIso8601String(),
            'owner_id' => $exception->owner_user_id ?: $exception->owner_id,
            'sla_metric' => $payload['metric_name'] ?? null,
        ];
    }
}

