<?php

namespace App\Filament\Resources\RoadHelperProfileResource\Pages;

use App\Filament\Resources\RoadHelperProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoadHelperProfile extends EditRecord
{
    protected static string $resource = RoadHelperProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
