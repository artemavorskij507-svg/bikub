<?php

namespace App\Filament\Resources\DispatchRuleSetResource\Pages;

use App\Filament\Resources\DispatchRuleSetResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispatchRuleSets extends ListRecords
{
    protected static string $resource = DispatchRuleSetResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
