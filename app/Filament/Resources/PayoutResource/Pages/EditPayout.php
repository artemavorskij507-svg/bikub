<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPayout extends EditRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Автоматически заполняем processed_by при изменении статуса
        if (isset($data['status']) && $data['status'] !== 'pending') {
            if (empty($data['processed_by'])) {
                $data['processed_by'] = Auth::id();
            }
            if (empty($data['processed_at'])) {
                $data['processed_at'] = now();
            }
        }

        return $data;
    }
}
