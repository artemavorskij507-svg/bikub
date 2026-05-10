<?php

namespace Database\Factories;

use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HandymanOrderDetails>
 */
class HandymanOrderDetailsFactory extends Factory
{
    protected $model = HandymanOrderDetails::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'handyman_service_id' => HandymanService::factory(),
            'is_custom_request' => false,
            'description' => $this->faker->sentence(10),
            'context_notes' => $this->faker->optional()->sentence(5),
            'needs_materials_purchase' => $this->faker->boolean(30),
            'materials_notes' => $this->faker->optional()->sentence(5),
            'expected_duration_minutes' => $this->faker->numberBetween(60, 240),
            'address_line' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'attachments' => null,
            'estimated_price_minor' => $this->faker->numberBetween(50000, 200000),
            'final_price_minor' => null,
            'desired_start_at' => null,
            'desired_finish_at' => null,
        ];
    }
}
