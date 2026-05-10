<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\Pages;

use App\Filament\Resources\Moving\MovingOrderResource;
use App\Models\Moving\MovingOrder;
use App\Services\Moving\MovingPriceCalculator;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditMovingOrder extends EditRecord
{
    protected static string $resource = MovingOrderResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('recalculate_price')
                ->label('Пересчитать цену')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->action(function () {
                    $this->record->recalculatePrice();
                    \Filament\Notifications\Notification::make()
                        ->title('Цена пересчитана')
                        ->body('Новая цена: '.number_format($this->record->estimated_price, 2).' NOK')
                        ->success()
                        ->send();
                    $this->refreshFormData(['estimated_price']);
                }),
            Actions\Action::make('recalculate_totals')
                ->label('Пересчитать объем и вес')
                ->icon('heroicon-o-refresh')
                ->color('info')
                ->action(function () {
                    $this->record->recalculateTotals();
                    \Filament\Notifications\Notification::make()
                        ->title('Объем и вес пересчитаны')
                        ->body('Объем: '.$this->record->total_volume.' м³, Вес: '.$this->record->total_weight.' кг')
                        ->success()
                        ->send();
                    $this->refreshFormData(['total_volume', 'total_weight']);
                }),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->prepareNumericAggregates($data);
        // Пересчитываем цену если изменились влияющие параметры
        $record = $this->record;
        $shouldRecalculate = blank($data['estimated_price']) ||
            $this->hasPriceAffectingChanges($data, $record);
        if ($shouldRecalculate) {
            try {
                $order = new MovingOrder(Arr::except($data, ['items']));
                $order->id = $record->id; // Для корректного кэширования
                $calculator = app(MovingPriceCalculator::class);
                $data['estimated_price'] = $calculator->calculate($order);
            } catch (\Exception $e) {
                \Log::warning('Failed to recalculate price on edit', [
                    'order_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);
                // Не меняем цену если расчет не удался
            }
        }

        return $data;
    }

    protected function hasPriceAffectingChanges(array $data, MovingOrder $record): bool
    {
        return
            ($data['from_address'] ?? null) !== $record->from_address ||
            ($data['to_address'] ?? null) !== $record->to_address ||
            ($data['services'] ?? null) !== $record->services ||
            ($data['package_type'] ?? null) !== $record->package_type ||
            ($data['total_volume'] ?? null) != $record->total_volume ||
            ($data['total_weight'] ?? null) != $record->total_weight;
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
