<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoadsidePreset>
 */
class RoadsidePresetFactory extends Factory
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
                'code' => 'jump_start',
                'label' => 'Прикурить авто',
                'description' => 'Запуск двигуна через прикуривание від іншого автомобіля',
                'service_type' => 'roadside_assistance',
                'base_price' => 500,
                'requires_partner' => false,
            ],
            [
                'code' => 'tire_change',
                'label' => 'Замена колеса',
                'description' => 'Заміна проколотого колеса на запаске',
                'service_type' => 'roadside_assistance',
                'base_price' => 600,
                'requires_partner' => false,
            ],
            [
                'code' => 'fuel_delivery',
                'label' => 'Привезти топливо',
                'description' => 'Доставка палива до місця зупинки',
                'service_type' => 'roadside_assistance',
                'base_price' => 800,
                'requires_partner' => false,
            ],
            [
                'code' => 'basic_diagnostics',
                'label' => 'Лёгкая диагностика',
                'description' => 'Базова діагностика проблем з автомобілем',
                'service_type' => 'roadside_assistance',
                'base_price' => 700,
                'requires_partner' => false,
            ],
            [
                'code' => 'towing',
                'label' => 'Эвакуация',
                'description' => 'Евакуація автомобіля до СТО або іншого місця',
                'service_type' => 'vehicle_transport',
                'base_price' => 1500,
                'requires_partner' => true,
            ],
            [
                'code' => 'winching',
                'label' => 'Вытаскивание',
                'description' => 'Витягування автомобіля зі снігу, грязі або іншої перешкоди',
                'service_type' => 'roadside_assistance',
                'base_price' => 1200,
                'requires_partner' => true,
            ],
            [
                'code' => 'lockout',
                'label' => 'Открытие замка',
                'description' => 'Відкриття автомобіля при закритих ключах всередині',
                'service_type' => 'roadside_assistance',
                'base_price' => 900,
                'requires_partner' => false,
            ],
        ];

        $preset = fake()->randomElement($presets);

        return [
            'code' => $preset['code'],
            'label' => $preset['label'],
            'description' => $preset['description'],
            'service_type' => $preset['service_type'],
            'base_price' => $preset['base_price'],
            'requires_partner' => $preset['requires_partner'],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'metadata' => [
                'estimated_duration_minutes' => fake()->numberBetween(15, 60),
                'typical_issues' => [],
            ],
        ];
    }

    /**
     * Create a specific preset by code.
     */
    public function withCode(string $code): static
    {
        $presets = [
            'jump_start' => [
                'code' => 'jump_start',
                'label' => 'Прикурить авто',
                'description' => 'Запуск двигуна через прикуривание від іншого автомобіля',
                'service_type' => 'roadside_assistance',
                'base_price' => 500,
                'requires_partner' => false,
            ],
            'tire_change' => [
                'code' => 'tire_change',
                'label' => 'Замена колеса',
                'description' => 'Заміна проколотого колеса на запаске',
                'service_type' => 'roadside_assistance',
                'base_price' => 600,
                'requires_partner' => false,
            ],
            'fuel_delivery' => [
                'code' => 'fuel_delivery',
                'label' => 'Привезти топливо',
                'description' => 'Доставка палива до місця зупинки',
                'service_type' => 'roadside_assistance',
                'base_price' => 800,
                'requires_partner' => false,
            ],
            'basic_diagnostics' => [
                'code' => 'basic_diagnostics',
                'label' => 'Лёгкая диагностика',
                'description' => 'Базова діагностика проблем з автомобілем',
                'service_type' => 'roadside_assistance',
                'base_price' => 700,
                'requires_partner' => false,
            ],
            'towing' => [
                'code' => 'towing',
                'label' => 'Эвакуация',
                'description' => 'Евакуація автомобіля до СТО або іншого місця',
                'service_type' => 'vehicle_transport',
                'base_price' => 1500,
                'requires_partner' => true,
            ],
        ];

        if (! isset($presets[$code])) {
            return $this;
        }

        $preset = $presets[$code];

        return $this->state(fn (array $attributes) => [
            'code' => $preset['code'],
            'label' => $preset['label'],
            'description' => $preset['description'],
            'service_type' => $preset['service_type'],
            'base_price' => $preset['base_price'],
            'requires_partner' => $preset['requires_partner'],
        ]);
    }
}
