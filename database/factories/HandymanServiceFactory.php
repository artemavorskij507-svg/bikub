<?php

namespace Database\Factories;

use App\Models\HandymanService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HandymanService>
 */
class HandymanServiceFactory extends Factory
{
    protected $model = HandymanService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'code' => Str::slug($name),
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => $this->faker->sentence(10),
            'category' => $this->faker->randomElement(['plumbing', 'electrical', 'furniture', 'other']),
            'pricing_mode' => $this->faker->randomElement([HandymanService::PRICING_FIXED, HandymanService::PRICING_HOURLY]),
            'base_rate_minor' => $this->faker->numberBetween(30000, 200000), // 300-2000 NOK
            'estimated_duration_minutes' => $this->faker->numberBetween(60, 480),
            'required_skills' => [],
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
