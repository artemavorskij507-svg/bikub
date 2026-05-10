<?php

namespace Database\Factories;

use App\Models\TrafficIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficIncident>
 */
class TrafficIncidentFactory extends Factory
{
    protected $model = TrafficIncident::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 hours', 'now');

        return [
            'external_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(8),
            'severity' => $this->faker->randomElement(['minor', 'moderate', 'severe']),
            'status' => $this->faker->randomElement(['active', 'resolved']),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 hours'),
            'lat' => $this->faker->latitude(67.0, 70.0),
            'lng' => $this->faker->longitude(13.0, 19.0),
            'geometry' => null,
            'meta' => [],
            'source_url' => $this->faker->optional()->url(),
        ];
    }
}
