<?php

namespace Database\Seeders;

use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\AdFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ClassifiedsSeeder extends Seeder
{
    public function run(): void
    {
        // --- FEATURES ---
        $featuresData = [
            [
                'name' => 'Город',
                'code' => 'city',
                'field_type' => 'text',
                'options' => null,
                'is_required' => true,
            ],
            [
                'name' => 'Состояние',
                'code' => 'condition',
                'field_type' => 'select',
                'options' => [
                    ['label' => 'Новое', 'value' => 'new'],
                    ['label' => 'Как новое', 'value' => 'like_new'],
                    ['label' => 'Б/у', 'value' => 'used'],
                ],
                'is_required' => false,
            ],
            // Авто
            [
                'name' => 'Марка',
                'code' => 'car_make',
                'field_type' => 'text',
                'options' => null,
                'is_required' => true,
            ],
            [
                'name' => 'Модель',
                'code' => 'car_model',
                'field_type' => 'text',
                'options' => null,
                'is_required' => true,
            ],
            [
                'name' => 'Год выпуска',
                'code' => 'year',
                'field_type' => 'number',
                'options' => null,
                'is_required' => false,
            ],
            [
                'name' => 'Пробег (км)',
                'code' => 'mileage',
                'field_type' => 'number',
                'options' => null,
                'is_required' => false,
            ],
            [
                'name' => 'Тип топлива',
                'code' => 'fuel_type',
                'field_type' => 'select',
                'options' => [
                    ['label' => 'Бензин', 'value' => 'petrol'],
                    ['label' => 'Дизель', 'value' => 'diesel'],
                    ['label' => 'Электро', 'value' => 'ev'],
                    ['label' => 'Гибрид', 'value' => 'hybrid'],
                ],
                'is_required' => false,
            ],
            // Недвижимость
            [
                'name' => 'Комнаты',
                'code' => 'rooms',
                'field_type' => 'number',
                'options' => null,
                'is_required' => false,
            ],
            [
                'name' => 'Площадь (м²)',
                'code' => 'area',
                'field_type' => 'number',
                'options' => null,
                'is_required' => false,
            ],
            [
                'name' => 'Тип объекта',
                'code' => 'property_type',
                'field_type' => 'select',
                'options' => [
                    ['label' => 'Квартира', 'value' => 'flat'],
                    ['label' => 'Дом', 'value' => 'house'],
                    ['label' => 'Комната', 'value' => 'room'],
                ],
                'is_required' => false,
            ],
            // Работа
            [
                'name' => 'Тип занятости',
                'code' => 'employment_type',
                'field_type' => 'select',
                'options' => [
                    ['label' => 'Полная занятость', 'value' => 'full_time'],
                    ['label' => 'Частичная занятость', 'value' => 'part_time'],
                    ['label' => 'Подработка', 'value' => 'temporary'],
                ],
                'is_required' => false,
            ],
            [
                'name' => 'Удалённая работа',
                'code' => 'remote',
                'field_type' => 'checkbox',
                'options' => null,
                'is_required' => false,
            ],
        ];

        $features = collect();
        foreach ($featuresData as $data) {
            $features->put(
                $data['code'],
                AdFeature::updateOrCreate(
                    ['code' => $data['code']],
                    Arr::only($data, ['name', 'field_type', 'options', 'is_required'])
                )
            );
        }

        // --- CATEGORIES ---
        $categoriesConfig = [
            [
                'name' => 'Недвижимость',
                'slug' => 'real-estate',
                'features' => ['city', 'condition', 'rooms', 'area', 'property_type'],
            ],
            [
                'name' => 'Авто и транспорт',
                'slug' => 'cars',
                'features' => ['city', 'condition', 'car_make', 'car_model', 'year', 'mileage', 'fuel_type'],
            ],
            [
                'name' => 'Электроника',
                'slug' => 'electronics',
                'features' => ['city', 'condition'],
            ],
            [
                'name' => 'Мебель и интерьер',
                'slug' => 'furniture',
                'features' => ['city', 'condition'],
            ],
            [
                'name' => 'Работа',
                'slug' => 'jobs',
                'features' => ['city', 'employment_type', 'remote'],
            ],
            [
                'name' => 'Услуги',
                'slug' => 'services',
                'features' => ['city'],
            ],
        ];

        foreach ($categoriesConfig as $cfg) {
            /** @var AdCategory $category */
            $category = AdCategory::updateOrCreate(
                ['slug' => $cfg['slug']],
                [
                    'name' => $cfg['name'],
                    'parent_id' => null,
                    'meta_title' => $cfg['name'],
                    'meta_description' => null,
                    'is_active' => true,
                ]
            );

            $featureIds = collect($cfg['features'])
                ->map(fn (string $code) => $features->get($code)?->id)
                ->filter()
                ->values()
                ->all();

            if (! empty($featureIds)) {
                $category->features()->syncWithoutDetaching($featureIds);
            }
        }
    }
}
