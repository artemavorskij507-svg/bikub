<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'description' => $this->faker->optional()->paragraphs(3, true),
            'image_url' => $this->faker->optional()->imageUrl(640, 480, 'technics', true),
            'is_active' => true,
        ];
    }
}
