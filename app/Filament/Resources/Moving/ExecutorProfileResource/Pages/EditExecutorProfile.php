<?php

namespace App\Filament\Resources\Moving\ExecutorProfileResource\Pages;

use App\Filament\Resources\Moving\ExecutorProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExecutorProfile extends EditRecord
{
    protected static string $resource = ExecutorProfileResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Перетворюємо масив навичок у рядок для відображення в Textarea
        if (isset($data['skills']) && is_array($data['skills'])) {
            $data['skills'] = implode(', ', $data['skills']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Перетворюємо рядок навичок у масив
        if (isset($data['skills']) && is_string($data['skills'])) {
            $data['skills'] = array_filter(
                array_map('trim', explode(',', $data['skills']))
            );
        }

        return $data;
    }
}
