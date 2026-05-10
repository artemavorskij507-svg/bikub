<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OpsOverviewStats;
use Filament\Pages\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Operations Core';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected function getActions(): array
    {
        return [
            Action::make('operations_core')
                ->label('Unified Operations Core')
                ->url(fn () => UnifiedOperationsCore::getUrl())
                ->color('success')
                ->button(),
            Action::make('agent_chat')
                ->label('Team Chat')
                ->url(fn () => AgentTeamChat::getUrl())
                ->color('primary')
                ->button(),
        ];
    }

    protected function getWidgets(): array
    {
        return [
            OpsOverviewStats::class,
        ];
    }
}
