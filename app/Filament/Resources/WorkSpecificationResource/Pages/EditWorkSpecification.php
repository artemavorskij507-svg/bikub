<?php

namespace App\Filament\Resources\WorkSpecificationResource\Pages;

use App\Filament\Resources\WorkSpecificationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkSpecification extends EditRecord
{
    protected static string $resource = WorkSpecificationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
