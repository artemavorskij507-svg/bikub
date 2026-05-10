<?php

namespace App\Filament\Resources\AssistantConversationResource\Pages;

use App\Filament\Resources\AssistantConversationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssistantConversation extends EditRecord
{
    protected static string $resource = AssistantConversationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
