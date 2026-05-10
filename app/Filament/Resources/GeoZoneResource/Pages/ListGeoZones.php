<?php

namespace App\Filament\Resources\GeoZoneResource\Pages;

use App\Filament\Resources\GeoZoneResource;
use App\Services\Geo\GeoZoneService;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeoZones extends ListRecords
{
    protected static string $resource = GeoZoneResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('refresh_cache')
                ->label('Обновить кеш зон')
                ->icon('heroicon-o-refresh')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    app(GeoZoneService::class)->refreshCache();

                    Notification::make()
                        ->title('Кеш геозон обновлен')
                        ->success()
                        ->send();
                }),
        ];
    }
}
