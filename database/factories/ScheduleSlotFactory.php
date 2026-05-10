<?php

namespace Database\Factories;

use App\Models\GeoZone;
use App\Models\ScheduleSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScheduleSlot>
 */
class ScheduleSlotFactory extends Factory
{
    protected $model = ScheduleSlot::class;

    public function definition(): array
    {
        $code = Str::upper($this->faker->unique()->lexify('slot-???'));
        $startAt = $this->faker->dateTimeBetween('now', '+1 week');
        $endAt = (clone $startAt)->modify('+'.rand(30, 180).' minutes');

        return [
            'code' => $code,
            'name' => $this->faker->words(2, true),
            'zone_id' => GeoZone::factory(),
            'org_id' => null,
            'service_type_id' => null,
            'kind' => $this->faker->randomElement(['delivery', 'pickup', 'service', 'shuttle']),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'hard_window' => $this->faker->boolean(15),
            'buffer_before_min' => $this->faker->numberBetween(0, 30),
            'buffer_after_min' => $this->faker->numberBetween(0, 30),
            'capacity_total' => $this->faker->numberBetween(5, 20),
            'capacity_reserved' => 0,
            'capacity_confirmed' => 0,
            'max_orders' => $this->faker->numberBetween(5, 25),
            'courier_required' => $this->faker->numberBetween(1, 3),
            'courier_assigned' => 0,
            'max_distance_km' => $this->faker->optional()->randomFloat(2, 1, 25),
            'features' => $this->faker->optional()->randomElements([
                'priority-delivery',
                'chilled-storage',
                'evening-slot',
            ], $this->faker->numberBetween(0, 2)),
            'meta' => $this->faker->optional()->randomElement([
                ['notes' => 'Auto generated slot'],
                ['tags' => ['test']],
            ]),
            'status' => $this->faker->randomElement(['open', 'hold', 'locked', 'closed']),
            'lock_expires_at' => $this->faker->optional()->dateTimeBetween('+10 minutes', '+12 hours'),
        ];
    }
}
