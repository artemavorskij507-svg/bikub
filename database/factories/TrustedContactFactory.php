<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use App\Models\TrustedContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrustedContact>
 */
class TrustedContactFactory extends Factory
{
    protected $model = TrustedContact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_profile_id' => ClientProfile::factory(),
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'relationship' => fake()->randomElement(['дочь', 'сын', 'опекун', 'родственник', 'соцработник']),
            'phone' => '+47'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'can_manage_orders' => true,
            'can_view_reports' => true,
            'is_primary' => false,
        ];
    }
}
