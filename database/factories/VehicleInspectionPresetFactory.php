<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleInspectionPreset>
 */
class VehicleInspectionPresetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $presets = [
            [
                'title' => 'Предпокупочная проверка',
                'description' => 'Повна перевірка автомобіля перед покупкою: технічний стан, історія, документи',
                'price' => 2500,
                'checklist' => [
                    'Зовнішній огляд кузова',
                    'Перевірка двигуна',
                    'Перевірка ходової частини',
                    'Перевірка гальм',
                    'Перевірка електроніки',
                    'Перевірка документів',
                    'Перевірка історії автомобіля',
                ],
            ],
            [
                'title' => 'Базовый осмотр ходовой',
                'description' => 'Базова перевірка ходової частини: підвіска, гальма, шини',
                'price' => 1200,
                'checklist' => [
                    'Перевірка амортизаторів',
                    'Перевірка пружин',
                    'Перевірка сайлентблоків',
                    'Перевірка гальмових колодок',
                    'Перевірка гальмових дисків',
                    'Перевірка стану шин',
                ],
            ],
            [
                'title' => 'Проверка при продаже',
                'description' => 'Огляд автомобіля перед продажем для оцінки ринкової вартості',
                'price' => 1800,
                'checklist' => [
                    'Оцінка зовнішнього стану',
                    'Перевірка технічного стану',
                    'Оцінка вартості',
                    'Фотофіксація',
                    'Складання звіту',
                ],
            ],
            [
                'title' => 'Полная диагностика',
                'description' => 'Повна діагностика всіх систем автомобіля',
                'price' => 3500,
                'checklist' => [
                    'Діагностика двигуна',
                    'Діагностика коробки передач',
                    'Діагностика електроніки',
                    'Перевірка системи охолодження',
                    'Перевірка системи живлення',
                    'Перевірка вихлопної системи',
                    'Комп\'ютерна діагностика',
                ],
            ],
        ];

        $preset = fake()->randomElement($presets);

        return [
            'title' => $preset['title'],
            'slug' => Str::slug($preset['title']),
            'description' => $preset['description'],
            'price' => $preset['price'],
            'checklist' => $preset['checklist'],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'metadata' => [
                'estimated_duration_minutes' => fake()->numberBetween(30, 120),
                'inspection_type' => 'standard',
            ],
        ];
    }

    /**
     * Create a specific preset by title.
     */
    public function withTitle(string $title): static
    {
        $presets = [
            'Предпокупочная проверка' => [
                'title' => 'Предпокупочная проверка',
                'description' => 'Повна перевірка автомобіля перед покупкою: технічний стан, історія, документи',
                'price' => 2500,
                'checklist' => [
                    'Зовнішній огляд кузова',
                    'Перевірка двигуна',
                    'Перевірка ходової частини',
                    'Перевірка гальм',
                    'Перевірка електроніки',
                    'Перевірка документів',
                    'Перевірка історії автомобіля',
                ],
            ],
            'Базовый осмотр ходовой' => [
                'title' => 'Базовый осмотр ходовой',
                'description' => 'Базова перевірка ходової частини: підвіска, гальма, шини',
                'price' => 1200,
                'checklist' => [
                    'Перевірка амортизаторів',
                    'Перевірка пружин',
                    'Перевірка сайлентблоків',
                    'Перевірка гальмових колодок',
                    'Перевірка гальмових дисків',
                    'Перевірка стану шин',
                ],
            ],
            'Проверка при продаже' => [
                'title' => 'Проверка при продаже',
                'description' => 'Огляд автомобіля перед продажем для оцінки ринкової вартості',
                'price' => 1800,
                'checklist' => [
                    'Оцінка зовнішнього стану',
                    'Перевірка технічного стану',
                    'Оцінка вартості',
                    'Фотофіксація',
                    'Складання звіту',
                ],
            ],
        ];

        if (! isset($presets[$title])) {
            return $this;
        }

        $preset = $presets[$title];

        return $this->state(fn (array $attributes) => [
            'title' => $preset['title'],
            'slug' => Str::slug($preset['title']),
            'description' => $preset['description'],
            'price' => $preset['price'],
            'checklist' => $preset['checklist'],
        ]);
    }
}
