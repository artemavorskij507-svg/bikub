<?php

namespace App\Filament\Resources\AnalyticsResource\Pages;

use App\Filament\Resources\AnalyticsResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalytics extends ListRecords
{
    protected static string $resource = AnalyticsResource::class;

    protected function getActions(): array
    {
        return [
            // Removed CreateAction - analytics is read-only
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('advanced_analytics')
                ->label('Расширенная аналитика')
                ->url('/admin/analytics')
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    protected function canCreate(): bool
    {
        return false; // Explicitly disable creation
    }
}
