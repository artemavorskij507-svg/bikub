<?php

namespace Database\Factories;

use App\Models\ErrandTask;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class ErrandTaskFactory extends Factory
{
    protected $model = ErrandTask::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'title' => $this->faker->sentence(4),
            'category' => 'courier',
            'sub_category' => null,
            'status' => 'draft',
            'priority' => 'normal',
            'customer_name' => $this->faker->name(),
            'customer_phone' => '+47 '.$this->faker->numberBetween(40000000, 49999999),
            'pickup_address' => 'Test street 1, Narvik',
            'dropoff_address' => 'Test street 2, Narvik',
            'pickup_location' => ['lat' => 68.438, 'lng' => 17.427],
            'dropoff_location' => ['lat' => 68.439, 'lng' => 17.430],
            'waypoints' => [],
            'contacts' => [],
            'notes' => $this->faker->sentence(),
            'is_urgent' => false,
            'requires_signature' => false,
            'requires_trusted_helper' => false,
            'requires_document_handling' => false,
            'expected_duration_minutes' => 30,
            'expected_distance_km' => 5,
            'complexity_level' => 1,
            'risk_score' => 0,
            'material_advance_amount' => 0,
            'pricing_snapshot' => [],
            'meta' => [],
        ];
    }
}
