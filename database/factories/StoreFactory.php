<?php

namespace Database\Factories;

use App\Models\GeoZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = $this->faker->company.' '.$this->faker->randomElement(['Narvik', 'Tromsø', 'Oslo']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'zone_id' => GeoZone::factory(),
            'logo_url' => $this->faker->optional()->imageUrl(120, 120, 'business', true),
            'banner_url' => $this->faker->optional()->imageUrl(800, 300, 'city', true),
            'is_active' => true,
            'order_column' => $this->faker->numberBetween(0, 50),
        ];
    }
}
