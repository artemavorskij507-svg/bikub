<?php

namespace App\Filament\Resources\AdCategoryResource\Pages;

use App\Filament\Resources\AdCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAdCategory extends CreateRecord
{
    protected static string $resource = AdCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Категория успешно создана!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Убеждаемся, что slug заполнен
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
