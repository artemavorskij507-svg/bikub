<?php

namespace App\Filament\Resources\WorkSpecificationResource\Pages;

use App\Filament\Resources\WorkSpecificationResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkSpecification extends CreateRecord
{
    protected static string $resource = WorkSpecificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Автоматически устанавливаем создателя
        if (! isset($data['creator_id']) && auth()->check()) {
            $data['creator_id'] = auth()->id();
        }

        // Генерируем public_id если не указан
        if (empty($data['public_id'])) {
            $data['public_id'] = 'WS-'.strtoupper(uniqid());
        }

        // Обрезаем пробелы в названии
        if (isset($data['title'])) {
            $data['title'] = trim($data['title']);
        }

        // Убеждаемся, что metadata это массив
        if (isset($data['metadata']) && ! is_array($data['metadata'])) {
            $data['metadata'] = [];
        }

        return $data;
    }

    protected function getFormValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('ТЗ создано')
            ->body('Техническое задание успешно создано и готово к работе.');
    }
}
