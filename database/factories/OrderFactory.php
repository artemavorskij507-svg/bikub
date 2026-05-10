<?php

namespace Database\Factories;

use App\Enums\PaymentFlow;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $estimated = $this->faker->numberBetween(5_000, 25_000);
        $buffer = (int) round($estimated * 1.2);

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper($this->faker->unique()->bothify('??##')),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'assigned_to' => null,
            'service_type' => 'delivery',
            'status' => 'pending_payment',
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'notes' => $this->faker->optional()->sentence(),
            'location' => [
                'pickup' => [
                    'address' => $this->faker->address(),
                    'lat' => $this->faker->latitude(67.0, 70.0),
                    'lng' => $this->faker->longitude(13.0, 19.0),
                ],
            ],
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('-1 day', '+2 days'),
            'started_at' => null,
            'completed_at' => null,
            'total_amount' => $estimated / 100,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'payment_flow' => PaymentFlow::AuthorizeCapture,
            'payment_method' => null,
            'payment_intent_id' => null,
            'receipt_url' => null,
            'estimated_total' => $estimated,
            'buffer_total' => $buffer,
            'actual_total' => null,
            'metadata' => ['channel' => $this->faker->randomElement(['app', 'web', 'partner'])],
        ];
    }
}
