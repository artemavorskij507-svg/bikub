<?php

namespace App\Filament\Resources\SocialCareOrderResource\Pages;

use App\Filament\Resources\SocialCareOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialCareOrder extends EditRecord
{
    protected static string $resource = SocialCareOrderResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make()];
    }
}
