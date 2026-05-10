<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\Pages;

use App\Filament\Resources\Moving\MovingOrderResource;
use App\Models\Moving\MovingOrder;
use App\Services\Moving\MovingPriceCalculator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateMovingOrder extends CreateRecord
{
    protected static string $resource = MovingOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->prepareNumericAggregates($data);
        // Автоматически устанавливаем user_id если не указан
        if (empty($data['user_id']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }
        // Пересчитываем цену если не указана или изменились параметры
        if (empty($data['estimated_price']) || $this->shouldRecalculatePrice($data)) {
            try {
                $order = new MovingOrder(Arr::except($data, ['items']));
                $calculator = app(MovingPriceCalculator::class);
                $data['estimated_price'] = $calculator->calculate($order);
            } catch (\Exception $e) {
                \Log::warning('Failed to calculate price on create', [
                    'error' => $e->getMessage(),
                    'data' => Arr::except($data, ['items']),
                ]);
                // Используем базовую цену как fallback
                $data['estimated_price'] = config('moving.base_price', 500);
            }
        }

        return $data;
    }

    protected function shouldRecalculatePrice(array $data): bool
    {
        // Пересчитываем если изменились влияющие параметры
        return isset($data['from_address'], $data['to_address'], $data['services'], $data['package_type']) ||
               isset($data['total_volume'], $data['total_weight']);
    }

    protected function prepareNumericAggregates(array $data): array
    {
        $items = collect($data['items'] ?? []);
        if (blank($data['total_volume'])) {
            $data['total_volume'] = $items->sum(fn ($item) => ($item['volume'] ?? 0) * ($item['quantity'] ?? 1));
        }
        if (blank($data['total_weight'])) {
            $data['total_weight'] = $items->sum(fn ($item) => ($item['weight'] ?? 0) * ($item['quantity'] ?? 1));
        }
        $data['inventory'] = $items->map(fn ($item) => Arr::only($item, ['name', 'category', 'quantity']))->values()->all();

        return $data;
    }
}
