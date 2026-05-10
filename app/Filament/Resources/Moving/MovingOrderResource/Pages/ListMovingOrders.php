<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\Pages;

use App\Filament\Resources\Moving\MovingOrderResource;
use App\Support\Local\MovingLocalDemoSeeder;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovingOrders extends ListRecords
{
    protected static string $resource = MovingOrderResource::class;

    public function mount(): void
    {
        parent::mount();
        MovingLocalDemoSeeder::run();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\MovingStatsWidget::class];
    }
}
