<?php

namespace App\Filament\Resources\ExecutorShiftResource\Pages;

use App\Filament\Resources\ExecutorShiftResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExecutorShifts extends ListRecords
{
    protected static string $resource = ExecutorShiftResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
