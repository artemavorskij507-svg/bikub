<?php

namespace App\Filament\Resources\Moving\MovingItemResource\Pages;

use App\Filament\Resources\Moving\MovingItemResource;
use App\Support\Local\MovingLocalDemoSeeder;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovingItems extends ListRecords
{
    protected static string $resource = MovingItemResource::class;

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
