<?php

namespace App\Filament\Resources\Moving\MovingOrderPhotoResource\Pages;

use App\Filament\Resources\Moving\MovingOrderPhotoResource;
use App\Support\Local\MovingLocalDemoSeeder;
use Filament\Resources\Pages\ListRecords;

class ListMovingOrderPhotos extends ListRecords
{
    protected static string $resource = MovingOrderPhotoResource::class;

    public function mount(): void
    {
        MovingLocalDemoSeeder::run();
        parent::mount();
    }
}
