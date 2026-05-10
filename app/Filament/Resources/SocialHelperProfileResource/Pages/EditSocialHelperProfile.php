<?php

namespace App\Filament\Resources\SocialHelperProfileResource\Pages;

use App\Filament\Resources\SocialHelperProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialHelperProfile extends EditRecord
{
    protected static string $resource = SocialHelperProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make(),            Actions\DeleteAction::make()];
    }
}
