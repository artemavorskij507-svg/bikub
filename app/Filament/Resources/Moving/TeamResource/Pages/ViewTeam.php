<?php

namespace App\Filament\Resources\Moving\TeamResource\Pages;

use App\Filament\Resources\Moving\TeamResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
