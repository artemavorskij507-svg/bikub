<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientProfile>
 */
class ClientProfileFactory extends Factory
{
    protected $model = ClientProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-90 years', '-18 years'),
            'phone' => '+47'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'address_line' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'mobility_notes' => fake()->optional()->sentence(),
            'health_notes' => fake()->optional()->sentence(),
            'communication_preferences' => [
                'preferred_language' => fake()->randomElement(['no', 'en', 'ru']),
                'preferred_contact_method' => fake()->randomElement(['phone', 'email', 'sms']),
            ],
            'is_active' => true,
        ];
    }
}
