<?php

namespace Database\Factories;

use App\Models\CareService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CareService>
 */
class CareServiceFactory extends Factory
{
    protected $model = CareService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = ['SOCIAL_HELPER', 'COMMUNITY_PARTNER', 'BIKUBE_FRIEND'];
        $names = [
            'Комплексная забота',
            'Сопровождение на прогулке',
            'Помощь по дому',
            'Медицинские напоминания',
            'Транспортировка',
        ];

        return [
            'code' => 'care-'.fake()->unique()->bothify('??##'),
            'name' => fake()->randomElement($names),
            'description' => fake()->optional()->paragraph(),
            'required_level' => fake()->randomElement($levels),
            'base_duration_minutes' => fake()->numberBetween(30, 180),
            'base_price_nok' => fake()->optional()->randomFloat(2, 300, 1500),
            'is_recurring_available' => fake()->boolean(60),
            'is_active' => true,
        ];
    }
}
