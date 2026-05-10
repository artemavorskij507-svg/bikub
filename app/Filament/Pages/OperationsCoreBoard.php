<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Queries\OpsSummaryQuery;

class OperationsCoreBoard extends UnifiedOperationsCore
{
    protected static ?string $slug = 'operations-core-board';

    protected static ?string $navigationLabel = 'Operations Core Board (Legacy)';

    protected static ?int $navigationSort = 999;

    protected static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(OpsSummaryQuery $summaryQuery): void
    {
        unset($summaryQuery);
        $this->redirect('/admin/operations-core');
    }
}

