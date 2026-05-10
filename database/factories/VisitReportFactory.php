<?php

namespace Database\Factories;

use App\Models\CareOrderDetails;
use App\Models\SocialHelperProfile;
use App\Models\VisitReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitReport>
 */
class VisitReportFactory extends Factory
{
    protected $model = VisitReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');
        $endedAt = (clone $startedAt)->modify('+1 hour');

        return [
            'care_order_details_id' => CareOrderDetails::factory(),
            'helper_profile_id' => SocialHelperProfile::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => 'COMPLETED',
            'summary' => fake()->optional()->paragraph(),
            'client_mood' => fake()->optional()->randomElement(['HAPPY', 'NEUTRAL', 'CONCERNED']),
            'issues_noted' => fake()->optional()->sentence(),
            'followup_recommended' => fake()->boolean(20),
            'followup_notes' => fake()->optional()->sentence(),
        ];
    }
}
