<?php

namespace Database\Factories;

use App\Models\HandymanAssignment;
use App\Models\Moving\ExecutorProfile;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HandymanAssignment>
 */
class HandymanAssignmentFactory extends Factory
{
    protected $model = HandymanAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'executor_profile_id' => ExecutorProfile::factory(),
            'repair_project_id' => null,
            'status' => $this->faker->randomElement(['proposed', 'accepted', 'declined', 'reassigned', 'cancelled', 'completed']),
            'planned_start_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
            'planned_finish_at' => $this->faker->optional()->dateTimeBetween('+1 week', '+2 weeks'),
            'actual_start_at' => null,
            'actual_finish_at' => null,
            'score' => $this->faker->numberBetween(0, 100),
            'is_primary' => $this->faker->boolean(30),
            'meta' => null,
        ];
    }
}
