<?php

namespace App\Filament\Widgets\Ops;

use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Widgets\Widget;

class ExecutorAvailabilityWidget extends Widget
{
    protected static string $view = 'filament.widgets.ops.executor-availability-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'items' => app(OpsSummaryQuery::class)->execute()['executors_availability'] ?? [],
        ];
    }
}

