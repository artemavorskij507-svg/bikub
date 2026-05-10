<?php

namespace Database\Factories;

use App\Models\ErrandOrderDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class ErrandOrderDetailsFactory extends Factory
{
    protected $model = ErrandOrderDetails::class;

    public function definition(): array
    {
        return [
            'order_id' => null, // в юнит-тестах нам не всегда нужен реальный Order
            'category' => $this->faker->randomElement(['documents', 'courier', 'shopping', 'queue', 'visit', 'custom']),
            'description' => $this->faker->sentence(),
            'from_address' => $this->faker->optional()->address(),
            'to_address' => $this->faker->optional()->address(),
            'from_lat' => $this->faker->optional()->latitude(),
            'from_lng' => $this->faker->optional()->longitude(),
            'to_lat' => $this->faker->optional()->latitude(),
            'to_lng' => $this->faker->optional()->longitude(),
            'waypoints' => null,
            'contacts' => null,
            'desired_start_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
            'desired_finish_at' => $this->faker->optional()->dateTimeBetween('+1 week', '+2 weeks'),
            'expected_duration_minutes' => 30,
            'complexity_level' => 2,
            'is_urgent' => false,
            'requires_signature' => false,
            'requires_trusted_helper' => false,
            'involves_documents' => false,
            'material_advance_amount' => 0,
            'base_fee' => null,
            'distance_fee' => null,
            'time_fee' => null,
            'complexity_fee' => null,
            'trusted_helper_fee' => null,
            'urgency_fee' => null,
            'total_estimated_price' => null,
            'dispatcher_id' => null,
            'executor_profile_id' => null,
            'meta' => null,
        ];
    }
}
