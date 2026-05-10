<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Actions\ResolveOrganizationScopeAction;
use App\Domain\Ops\Queries\LiveOperationsMapQuery;
use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Pages\Page;

class LiveOperationsMap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Live Operations Map';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'live-operations-map';

    protected static string $view = 'filament.pages.live-operations-map';

    public array $filters = [
        'domain' => null,
        'zone' => null,
        'status' => null,
        'at_risk_only' => false,
        'exceptions_only' => false,
        'executors_only' => false,
    ];

    public array $state = [];

    public array $summary = [];

    public function mount(
        LiveOperationsMapQuery $mapQuery,
        OpsSummaryQuery $summaryQuery,
        ResolveOrganizationScopeAction $resolveOrganizationScopeAction,
    ): void
    {
        $organizationId = $resolveOrganizationScopeAction->execute(auth()->user());
        $this->state = $mapQuery->execute($this->filters, $organizationId);
        $this->summary = $summaryQuery->execute($this->filters, $organizationId)['kpi'];
    }

    public function refreshData(
        LiveOperationsMapQuery $mapQuery,
        OpsSummaryQuery $summaryQuery,
        ResolveOrganizationScopeAction $resolveOrganizationScopeAction,
    ): void
    {
        $organizationId = $resolveOrganizationScopeAction->execute(auth()->user());
        $this->state = $mapQuery->execute($this->filters, $organizationId);
        $this->summary = $summaryQuery->execute($this->filters, $organizationId)['kpi'];
    }

    public function getTitle(): string
    {
        return 'Live Operations Map';
    }
}

