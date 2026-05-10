<?php

namespace App\Filament\Resources\Moving\ExecutorProfileResource\Pages;

use App\Filament\Resources\Moving\ExecutorProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListExecutorProfiles extends ListRecords
{
    protected static string $resource = ExecutorProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['user', 'kpi']);
    }
}
