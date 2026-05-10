<?php

namespace Database\Factories;

use App\Models\RepairProject;
use App\Models\RepairStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairStage>
 */
class RepairStageFactory extends Factory
{
    protected $model = RepairStage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repair_project_id' => RepairProject::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(10),
            'sequence' => $this->faker->numberBetween(10, 100),
            'status' => $this->faker->randomElement(['planned', 'in_progress', 'completed']),
            'planned_start_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'planned_finish_at' => $this->faker->optional()->dateTimeBetween('+1 month', '+3 months'),
            'actual_start_at' => null,
            'actual_finish_at' => null,
            'progress_percent' => $this->faker->numberBetween(0, 100),
        ];
    }
}
