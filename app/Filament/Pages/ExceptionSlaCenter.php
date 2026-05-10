<?php

namespace App\Filament\Pages;

use App\Domain\Ops\Queries\OpsSummaryQuery;
use Filament\Pages\Page;

class ExceptionSlaCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationLabel = 'Exception / SLA Center (Legacy)';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 999;

    protected static string $view = 'filament.pages.exception-sla-center';

    public $openExceptions = [];

    public array $slaSummary = [];

    public function mount(OpsSummaryQuery $summaryQuery): void
    {
        unset($summaryQuery);
        $this->redirect('/admin/operation-exceptions');
    }

    protected static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Exception / SLA Center';
    }
}
