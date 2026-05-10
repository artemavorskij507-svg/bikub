<?php

namespace App\Filament\Resources\Moving\TeamResource\Pages;

use App\Filament\Resources\Moving\TeamResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make(),            Actions\DeleteAction::make()];
    }
}
