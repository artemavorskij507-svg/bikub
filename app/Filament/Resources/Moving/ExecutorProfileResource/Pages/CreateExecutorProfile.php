<?php

namespace App\Filament\Resources\Moving\ExecutorProfileResource\Pages;

use App\Filament\Resources\Moving\ExecutorProfileResource;
use App\Models\Moving\ExecutorProfile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateExecutorProfile extends CreateRecord
{
    protected static string $resource = ExecutorProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Не допускаем создания второго профиля для того же пользователя
        if (isset($data['user_id']) && ExecutorProfile::where('user_id', $data['user_id'])->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Для цього користувача вже існує профіль виконавця. Відредагуйте існуючий профіль замість створення нового.',
            ]);
        }
        // Перетворюємо рядок навичок у масив
        if (isset($data['skills']) && is_string($data['skills'])) {
            $data['skills'] = array_filter(
                array_map('trim', explode(',', $data['skills']))
            );
        }

        return $data;
    }
}
