<?php

namespace App\Filament\Resources\Moving\TeamResource\Pages;

use App\Filament\Resources\Moving\TeamResource;
use App\Support\Local\MovingLocalDemoSeeder;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    public function mount(): void
    {
        parent::mount();
        MovingLocalDemoSeeder::run();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
