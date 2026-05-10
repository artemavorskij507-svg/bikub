<?php

namespace Database\Factories;

use App\Models\Moving\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Moving\ExecutorProfile>
 */
class ExecutorProfileFactory extends Factory
{
    protected $model = ExecutorProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_type' => $this->faker->randomElement(['van', 'truck', 'car']),
            'skills' => $this->faker->randomElements(['plumbing', 'electrical', 'carpentry', 'painting', 'tiling'], 2),
            'max_volume' => $this->faker->randomFloat(2, 10, 100),
            'max_weight' => $this->faker->randomFloat(2, 100, 1000),
            'insurance_limit' => $this->faker->randomFloat(2, 10000, 100000),
            'license_number' => $this->faker->unique()->numerify('LIC####'),
            'license_expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            'rating' => $this->faker->randomFloat(2, 3.0, 5.0),
            'completed_orders_count' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
