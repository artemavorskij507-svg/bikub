<?php

namespace App\Filament\Resources\RoadsidePresetResource\Pages;

use App\Filament\Resources\RoadsidePresetResource;
use App\Models\RoadsidePreset;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListRoadsidePresets extends ListRecords
{
    protected static string $resource = RoadsidePresetResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedRoadsidePresetsIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedRoadsidePresetsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('roadside_presets')) {
            return;
        }

        if (RoadsidePreset::query()->exists()) {
            return;
        }

        $rows = [
            [
                'code' => 'jump_start',
                'label' => 'Прикурить авто',
                'description' => 'Запуск автомобиля с разряженным аккумулятором.',
                'service_type' => 'roadside_assistance',
                'base_price' => 890,
                'requires_partner' => false,
                'is_active' => true,
                'sort_order' => 10,
                'metadata' => ['eta_min' => 30],
            ],
            [
                'code' => 'flat_tire',
                'label' => 'Замена колеса',
                'description' => 'Помощь при проколе и установка запасного колеса.',
                'service_type' => 'roadside_assistance',
                'base_price' => 990,
                'requires_partner' => false,
                'is_active' => true,
                'sort_order' => 20,
                'metadata' => ['eta_min' => 40],
            ],
            [
                'code' => 'tow_city',
                'label' => 'Эвакуация по городу',
                'description' => 'Транспортировка автомобиля в пределах города.',
                'service_type' => 'vehicle_transport',
                'base_price' => 1790,
                'requires_partner' => true,
                'is_active' => true,
                'sort_order' => 30,
                'metadata' => ['includes_km' => 15],
            ],
            [
                'code' => 'pre_purchase_check',
                'label' => 'Осмотр перед покупкой',
                'description' => 'Базовая диагностика автомобиля перед сделкой.',
                'service_type' => 'vehicle_inspection',
                'base_price' => 1490,
                'requires_partner' => false,
                'is_active' => true,
                'sort_order' => 40,
                'metadata' => ['inspection_level' => 'basic'],
            ],
        ];

        foreach ($rows as $row) {
            RoadsidePreset::query()->create($row);
        }
    }
}
