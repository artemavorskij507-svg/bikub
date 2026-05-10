<?php

namespace App\Filament\Widgets\Ops;

use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpsSummaryStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getCards(): array
    {
        $kpi = app(OpsSummaryQuery::class)->execute()['kpi'];

        return [
            Stat::make('Active jobs', (string) ($kpi['active_jobs'] ?? 0)),
            Stat::make('Pending dispatch', (string) ($kpi['pending_dispatch'] ?? 0)),
            Stat::make('Assigned', (string) ($kpi['assigned'] ?? 0)),
            Stat::make('In progress', (string) ($kpi['in_progress'] ?? 0)),
            Stat::make('At risk', (string) ($kpi['at_risk'] ?? 0)),
            Stat::make('Open exceptions', (string) ($kpi['open_exceptions'] ?? 0)),
            Stat::make('Avg dispatch', (string) ($kpi['avg_dispatch_time'] ?? 0).'m'),
            Stat::make('Avg arrival delay', (string) ($kpi['avg_arrival_delay'] ?? 0).'m'),
        ];
    }
}

