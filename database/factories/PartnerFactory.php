<?php

namespace Database\Factories;

use App\Models\GeoZone;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            Partner::TYPE_TOWING_SERVICE,
            'roadside_mobile',
            'repair_shop',
            'inspection_center',
            Partner::TYPE_SERVICE_STATION,
        ];

        $companyNames = [
            'Nordic Tow Service',
            'Arctic Roadside Assistance',
            'Fjord Towing AS',
            'Oslo Mobile Repair',
            'Bergen Auto Service',
            'Trondheim Tow Experts',
        ];

        $capabilities = [
            'jump_start' => 'Прикуривание',
            'wheel_change' => 'Замена колеса',
            'fuel_delivery' => 'Подвоз топлива',
            'towing' => 'Эвакуация',
            'winching' => 'Вытаскивание',
            'diagnostics' => 'Диагностика',
        ];

        $selectedCapabilities = fake()->randomElements(
            array_keys($capabilities),
            fake()->numberBetween(2, 4)
        );

        return [
            'name' => fake()->randomElement($companyNames).' '.fake()->randomElement(['AS', 'Ltd', 'AB']),
            'type' => fake()->randomElement($types),
            'slug' => fake()->unique()->slug(),
            'phone' => '+47 '.fake()->numerify('#### ####'),
            'email' => fake()->unique()->companyEmail(),
            'website' => 'https://'.fake()->domainName(),
            'geo_zone_id' => GeoZone::inRandomOrder()->first()?->id,
            'capabilities' => array_combine(
                $selectedCapabilities,
                array_map(fn ($key) => $capabilities[$key], $selectedCapabilities)
            ),
            'service_area' => null, // Можна розширити пізніше
            'is_active' => true,
            'active' => true,
            'is_available' => fake()->boolean(80),
            'priority' => fake()->numberBetween(10, 100),
            'rating_avg' => fake()->randomFloat(1, 4.0, 5.0),
            'on_time_rate' => fake()->randomFloat(2, 0.85, 0.98),
            'emergency_price_base' => fake()->randomFloat(2, 500, 2000),
            'emergency_price_per_km' => fake()->randomFloat(2, 15, 50),
            'metadata' => [
                'sla_minutes' => fake()->numberBetween(30, 90),
                'working_hours' => [
                    'monday' => ['08:00', '20:00'],
                    'tuesday' => ['08:00', '20:00'],
                    'wednesday' => ['08:00', '20:00'],
                    'thursday' => ['08:00', '20:00'],
                    'friday' => ['08:00', '20:00'],
                    'saturday' => ['09:00', '18:00'],
                    'sunday' => ['10:00', '16:00'],
                ],
            ],
        ];
    }

    /**
     * Indicate that the partner is a towing service.
     */
    public function towingService(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Partner::TYPE_TOWING_SERVICE,
            'capabilities' => ['towing' => 'Эвакуация', 'winching' => 'Вытаскивание'],
            'priority' => fake()->numberBetween(10, 50),
        ]);
    }

    /**
     * Indicate that the partner is a mobile roadside service.
     */
    public function mobileRoadside(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'roadside_mobile',
            'capabilities' => [
                'jump_start' => 'Прикуривание',
                'wheel_change' => 'Замена колеса',
                'fuel_delivery' => 'Подвоз топлива',
            ],
        ]);
    }
}
