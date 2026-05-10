<?php

namespace App\Filament\Resources\ErrandTaskResource\Pages;

use App\Filament\Resources\ErrandTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateErrandTask extends CreateRecord
{
    protected static string $resource = ErrandTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Безопасно убеждаемся, что order_id указан через валидацию Filament,
        // а не выбрасывая сырое исключение.
        if (empty($data['order_id'])) {
            $this->addError('order_id', 'Необходимо выбрать связанный заказ.');
        }
        // Поля с обязательным значением по умолчанию (в БД стоят NOT NULL).
        $feeFields = [
            'base_fee',
            'distance_fee',
            'time_fee',
            'complexity_fee',
            'trusted_helper_fee',
            'urgency_fee',
            'material_advance_amount',
            'estimated_total_amount',
        ];
        foreach ($feeFields as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $data[$field] = 0;
            }
        }
        // Приводим числовые поля к корректному типу, чтобы избежать ошибок при сохранении.
        foreach ([
            'expected_distance_km',
            'expected_duration_minutes',
            'material_advance_amount',
            'base_fee',
            'distance_fee',
            'time_fee',
            'complexity_fee',
            'trusted_helper_fee',
            'urgency_fee',
            'estimated_total_amount',
        ] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = (float) $data[$field];
            }
        }

        return $data;
    }
}
