<?php

namespace Database\Factories;

use App\Models\GeoZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GeoZone>
 */
class GeoZoneFactory extends Factory
{
    protected $model = GeoZone::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->city().' Zone';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement(['service_area', 'restricted_area', 'pickup_point']),
            'center_latitude' => $this->faker->latitude(67.0, 70.0),
            'center_longitude' => $this->faker->longitude(13.0, 19.0),
            'radius_meters' => $this->faker->numberBetween(500, 60000),
            'polygon_coordinates' => null,
            'is_active' => true,
            'metadata' => ['timezone' => $this->faker->timezone()],
            'org_id' => null,
        ];
    }
}
