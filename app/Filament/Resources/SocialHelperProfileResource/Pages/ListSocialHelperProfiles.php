<?php

namespace App\Filament\Resources\SocialHelperProfileResource\Pages;

use App\Filament\Resources\SocialHelperProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialHelperProfiles extends ListRecords
{
    protected static string $resource = SocialHelperProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
