<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'charge',
            'currency' => 'NOK',
            'amount_minor' => fake()->numberBetween(1000, 10000),
            'provider' => 'stripe',
            'status' => 'succeeded',
            'processed_at' => now(),
        ];
    }
}
