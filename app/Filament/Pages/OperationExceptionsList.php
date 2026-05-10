<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Queries\OpsSummaryQuery;

class OperationExceptionsList extends ExceptionSlaCenter
{
    protected static ?string $navigationLabel = 'Operation Exceptions (Legacy)';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 999;

    protected static ?string $slug = 'operation-exceptions-list';

    public function getTitle(): string
    {
        return 'Operation Exceptions List';
    }

    protected static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(OpsSummaryQuery $summaryQuery): void
    {
        unset($summaryQuery);
        $this->redirect('/admin/operation-exceptions');
    }
}
