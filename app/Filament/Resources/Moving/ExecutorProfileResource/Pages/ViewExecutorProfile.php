<?php

namespace App\Filament\Resources\Moving\ExecutorProfileResource\Pages;

use App\Filament\Resources\Moving\ExecutorProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExecutorProfile extends ViewRecord
{
    protected static string $resource = ExecutorProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
