<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoadHelperProfile>
 */
class RoadHelperProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skills = [
            'jump_start' => 'Прикуривание',
            'tire_change' => 'Замена колеса',
            'fuel_delivery' => 'Подвоз топлива',
            'basic_diagnostics' => 'Базовая диагностика',
            'towing' => 'Эвакуация',
        ];

        $equipment = [
            'jumper_cables' => 'Кабели для прикуривания',
            'tire_repair_kit' => 'Набор для ремонта шин',
            'fuel_can' => 'Канистра для топлива',
            'basic_tools' => 'Базовый набор инструментов',
        ];

        $vehicleTypes = ['van', 'pickup', 'truck', 'suv'];
        $vehicleModels = ['Ford Transit', 'Mercedes Sprinter', 'Volkswagen Crafter', 'Toyota Hilux'];

        $statuses = ['available', 'on_duty', 'off_duty', 'busy'];

        // Норвежські координати (приблизно Осло)
        $osloLat = 59.9139;
        $osloLng = 10.7522;

        return [
            'user_id' => User::factory(),
            'vehicle_type' => fake()->randomElement($vehicleTypes),
            'vehicle_model' => fake()->randomElement($vehicleModels),
            'vehicle_number' => fake()->regexify('[A-Z]{2}[0-9]{5}'),
            'equipment' => fake()->randomElements(
                array_keys($equipment),
                fake()->numberBetween(2, 4)
            ),
            'skills' => fake()->randomElements(
                array_keys($skills),
                fake()->numberBetween(2, 4)
            ),
            'current_status' => fake()->randomElement($statuses),
            'location_lat' => fake()->latitude($osloLat - 0.5, $osloLat + 0.5),
            'location_lng' => fake()->longitude($osloLng - 0.5, $osloLng + 0.5),
            'metadata' => [
                'experience_years' => fake()->numberBetween(1, 10),
                'rating' => fake()->randomFloat(1, 4.0, 5.0),
                'completed_jobs' => fake()->numberBetween(10, 500),
            ],
        ];
    }

    /**
     * Indicate that the helper is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_status' => 'available',
        ]);
    }
}
