<?php

namespace App\Filament\Resources\HandymanAssignmentResource\Pages;

use App\Filament\Resources\HandymanAssignmentResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHandymanAssignment extends EditRecord
{
    protected static string $resource = HandymanAssignmentResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
