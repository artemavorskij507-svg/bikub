<?php

namespace App\Filament\Resources\ExecutorBreakResource\Pages;

use App\Filament\Resources\ExecutorBreakResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExecutorBreaks extends ListRecords
{
    protected static string $resource = ExecutorBreakResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
