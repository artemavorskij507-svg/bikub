<?php

namespace App\Filament\Widgets\Ops;

use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Widgets\Widget;

class RecentReassignmentsWidget extends Widget
{
    protected static string $view = 'filament.widgets.ops.recent-reassignments-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'items' => app(OpsSummaryQuery::class)->execute()['recent_reassignments'] ?? [],
        ];
    }
}

