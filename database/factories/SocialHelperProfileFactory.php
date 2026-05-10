<?php

namespace Database\Factories;

use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialHelperProfile>
 */
class SocialHelperProfileFactory extends Factory
{
    protected $model = SocialHelperProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = ['SOCIAL_HELPER', 'COMMUNITY_PARTNER', 'BIKUBE_FRIEND'];
        $skills = ['companionship', 'shopping', 'medication_reminder', 'light_housekeeping', 'transportation'];

        return [
            'user_id' => User::factory(),
            'level' => fake()->randomElement($levels),
            'display_name' => fake()->name(),
            'bio' => fake()->optional()->paragraph(),
            'skills' => fake()->randomElements($skills, fake()->numberBetween(1, 3)),
            'has_police_certificate' => fake()->boolean(70),
            'police_certificate_verified_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'first_aid_trained_at' => fake()->optional(0.8)->dateTimeBetween('-2 years', 'now'),
            'rating_avg' => fake()->randomFloat(1, 3.5, 5.0),
            'rating_count' => fake()->numberBetween(0, 50),
            'is_active' => true,
            'available_from' => fake()->time('H:i:s'),
            'available_to' => fake()->time('H:i:s'),
        ];
    }
}
