<?php

namespace Database\Factories;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        $pickup = [
            'address' => $this->faker->address(),
            'lat' => $this->faker->latitude(67, 70),
            'lng' => $this->faker->longitude(13, 19),
        ];

        $delivery = [
            'address' => $this->faker->address(),
            'lat' => $this->faker->latitude(67, 70),
            'lng' => $this->faker->longitude(13, 19),
        ];

        return [
            'order_id' => Order::factory(),
            'type' => DeliveryType::GROCERY,
            'pickup_location' => $pickup,
            'delivery_location' => $delivery,
            'pickup_address' => $pickup['address'],
            'delivery_address' => $delivery['address'],
            'estimated_distance_km' => $this->faker->randomFloat(2, 1, 25),
            'estimated_duration_minutes' => $this->faker->numberBetween(10, 120),
            'eta' => now()->addMinutes($this->faker->numberBetween(15, 90)),
            'tracking_status' => DeliveryTrackingStatus::PENDING,
            'substitution_policy' => SubstitutionPolicy::STRICT,
            'is_urgent' => false,
            'metadata' => [],
            'tracking_token' => (string) Str::uuid(),
        ];
    }
}
