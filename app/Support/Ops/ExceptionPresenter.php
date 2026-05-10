<?php

namespace App\Support\Ops;

use App\Models\Operations\OperationException;

class ExceptionPresenter
{
    public static function value(OperationException $exception): string
    {
        $value = $exception->type
            ?? $exception->exception_type
            ?? $exception->canonical_type
            ?? 'unknown';

        return strtolower((string) $value);
    }

    public static function label(?string $type): string
    {
        $normalized = strtolower((string) $type);

        return match ($normalized) {
            'sla_warning' => 'SLA Warning',
            'sla_breach', 'breach' => 'SLA Breach',
            'stale_location_ping', 'stale_gps' => 'Stale GPS Ping',
            'assignment_stalled' => 'Assignment Stalled',
            'work_not_started_after_arrival' => 'Work Not Started',
            'no_executor_found' => 'No Executor Found',
            default => $normalized !== ''
                ? ucfirst(str_replace('_', ' ', $normalized))
                : 'Unknown',
        };
    }
}
