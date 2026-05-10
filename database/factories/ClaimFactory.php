<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Claim>
 */
class ClaimFactory extends Factory
{
    protected $model = Claim::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'repair_project_id' => null,
            'opened_by_user_id' => fn (array $attributes) => $attributes['user_id'],
            'assigned_to_user_id' => null,
            'type' => $this->faker->randomElement(['quality', 'damage', 'delay', 'billing', 'other']),
            'status' => $this->faker->randomElement(['open', 'in_review', 'resolved', 'rejected', 'closed']),
            'severity' => $this->faker->optional()->randomElement(['low', 'medium', 'high']),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'resolution_notes' => null,
            'resolution_type' => null,
            'opened_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'resolved_at' => null,
            'meta' => null,
        ];
    }
}
