<?php

namespace App\Support\Ops;

use App\Models\Operations\ServiceJob;

class SlaLabelPresenter
{
    public static function stateForJob(ServiceJob $job): string
    {
        $timer = $job->relationLoaded('slaTimers')
            ? $job->slaTimers->sortByDesc('id')->first()
            : $job->slaTimers()->latest('id')->first();

        if (! $timer) {
            return 'ok';
        }

        $canonical = strtolower((string) ($timer->status ?? ''));
        if (in_array($canonical, ['warning', 'breached', 'resolved', 'pending'], true)) {
            return match ($canonical) {
                'breached' => 'breached',
                'warning' => 'warning',
                default => 'ok',
            };
        }

        $legacyStates = [
            strtolower((string) ($timer->dispatch_state ?? '')),
            strtolower((string) ($timer->arrival_state ?? '')),
            strtolower((string) ($timer->completion_state ?? '')),
        ];

        if (in_array('breached', $legacyStates, true)) {
            return 'breached';
        }
        if (in_array('warning', $legacyStates, true)) {
            return 'warning';
        }

        return 'ok';
    }

    public static function label(string $state): string
    {
        return match ($state) {
            'breached' => 'Breached',
            'warning' => 'Warning',
            default => 'OK',
        };
    }

    public static function color(string $state): string
    {
        return match ($state) {
            'breached' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };
    }
}

