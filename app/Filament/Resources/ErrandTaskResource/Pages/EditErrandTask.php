<?php

namespace App\Filament\Resources\ErrandTaskResource\Pages;

use App\Filament\Resources\ErrandTaskResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditErrandTask extends EditRecord
{
    protected static string $resource = ErrandTaskResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
