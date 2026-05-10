<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Pages\Page;

class UnifiedOperationsCore extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-square-bar';

    protected static ?string $navigationLabel = 'Операционный центр';

    protected static ?string $navigationGroup = 'Операции';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'operations-core';

    protected static string $view = 'filament.pages.unified-operations-core';

    public array $filters = [
        'domain' => null,
        'zone' => null,
        'status' => null,
        'at_risk_only' => false,
        'exceptions_only' => false,
        'executors_only' => false,
    ];

    public array $state = [];

    public function mount(OpsSummaryQuery $summaryQuery): void
    {
        $this->state = $summaryQuery->execute($this->filters);
    }

    public function refreshData(OpsSummaryQuery $summaryQuery): void
    {
        $this->state = $summaryQuery->execute($this->filters);
    }

    public function getTitle(): string
    {
        return 'Операционный центр Bikubi';
    }
}

