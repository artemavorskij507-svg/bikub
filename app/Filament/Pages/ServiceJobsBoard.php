<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Queries\OpsSummaryQuery;

class ServiceJobsBoard extends UnifiedOperationsCore
{
    protected static ?string $navigationLabel = 'Service Jobs Board (Legacy)';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 999;

    protected static ?string $slug = 'service-jobs-board';

    protected static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Service Jobs Board';
    }

    public function mount(OpsSummaryQuery $summaryQuery): void
    {
        unset($summaryQuery);
        $this->redirect('/admin/service-jobs');
    }
}
