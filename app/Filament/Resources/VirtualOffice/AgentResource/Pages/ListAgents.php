<?php

namespace App\Filament\Resources\VirtualOffice\AgentResource\Pages;

use App\Filament\Resources\VirtualOffice\AgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgents extends ListRecords
{
    protected static string $resource = AgentResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
