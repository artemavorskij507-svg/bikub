<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\VirtualOfficeStatsWidget;
use Filament\Pages\Page;

class VirtualOfficeDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    protected static ?string $navigationLabel = 'Virtual Office';

    protected static ?string $navigationGroup = 'Virtual Office';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.virtual-office-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            VirtualOfficeStatsWidget::class,
        ];
    }
}
