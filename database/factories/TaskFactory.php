<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 day');

        return [
            'order_id' => Order::factory(),
            'parent_task_id' => null,
            'sequence_index' => $this->faker->numberBetween(0, 5),
            'type' => $this->faker->randomElement(['pickup', 'dropoff', 'service']),
            'status' => $this->faker->randomElement(['queued', 'in_progress']),
            'priority' => $this->faker->randomElement(['normal', 'high']),
            'zone_id' => GeoZone::factory(),
            'slot_id' => ScheduleSlot::factory(),
            'assignee_id' => Employee::factory(),
            'address_text' => $this->faker->address(),
            'lat' => $this->faker->latitude(67.0, 70.0),
            'lng' => $this->faker->longitude(13.0, 19.0),
            'window_start' => $start,
            'window_end' => (clone $start)->modify('+2 hours'),
            'expected_duration_min' => $this->faker->numberBetween(15, 120),
            'requirements' => ['vehicle' => $this->faker->randomElement(['van', 'car', 'bike'])],
            'price_component' => $this->faker->randomFloat(2, 50, 500),
            'payout_amount' => $this->faker->randomFloat(2, 20, 200),
            'currency' => 'NOK',
            'sla_deadline_at' => (clone $start)->modify('+3 hours'),
            'proof_required' => $this->faker->boolean(40),
            'instructions' => $this->faker->optional()->sentence(),
            'attachments' => [],
            'meta' => ['source' => $this->faker->randomElement(['manual', 'auto'])],
        ];
    }

    public function withoutAssignments(): self
    {
        return $this->state(fn () => [
            'assignee_id' => null,
            'slot_id' => null,
            'zone_id' => null,
        ]);
    }
}
