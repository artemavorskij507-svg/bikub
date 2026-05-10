<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RepairProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairProject>
 */
class RepairProjectFactory extends Factory
{
    protected $model = RepairProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'client_profile_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement(['draft', 'assessment', 'estimating', 'scheduled', 'in_progress']),
            'project_manager_id' => null,
            'address_line' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'planned_start_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'planned_finish_at' => $this->faker->optional()->dateTimeBetween('+2 months', '+6 months'),
            'actual_start_at' => null,
            'actual_finish_at' => null,
            'budget_estimate_minor' => $this->faker->optional()->numberBetween(500000, 2000000),
            'budget_actual_minor' => null,
            'design_project_url' => $this->faker->optional()->url(),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
