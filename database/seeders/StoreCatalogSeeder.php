<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\Product;
use App\Models\ProductStorePrice;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $zone = GeoZone::firstWhere('slug', 'narvik-main-service-area');

        $stores = [
            ['Rema 1000 Narvik', 'rema-1000-narvik', 10],
            ['Kiwi Narvik', 'kiwi-narvik', 20],
            ['Coop Extra Narvik', 'coop-extra-narvik', 30],
            ['Coop Prix Narvik', 'coop-prix-narvik', 40],
            ['Eurospar Narvik', 'eurospar-narvik', 50],
            ['Bunnpris Narvik', 'bunnpris-narvik', 60],
        ];

        foreach ($stores as $store) {
            Store::updateOrCreate(
                ['slug' => $store[1]],
                [
                    'name' => $store[0],
                    'zone_id' => $zone?->id,
                    'is_active' => true,
                    'order_column' => $store[2] ?? 0,
                ]
            );
        }

        $products = [
            [
                'name' => 'Norwegian Whole Milk 1L',
                'slug' => 'norwegian-whole-milk-1l',
                'description' => 'Свежая цельная молочная продукция 3.5% жирности.',
                'stores' => [
                    'rema-1000-narvik' => 1990,
                    'kiwi-narvik' => 1890,
                    'coop-prix-narvik' => 2090,
                ],
            ],
            [
                'name' => 'Grovbrød Whole Grain Bread',
                'slug' => 'grovbrod-whole-grain-bread',
                'description' => 'Норвежский цельнозерновой хлеб 750 г.',
                'stores' => [
                    'rema-1000-narvik' => 3490,
                    'coop-extra-narvik' => 3590,
                    'eurospar-narvik' => 3690,
                ],
            ],
            [
                'name' => 'Free-Range Eggs (12 pcs)',
                'slug' => 'free-range-eggs-12pcs',
                'description' => 'Свободный выгул, размер M, упаковка 12 штук.',
                'stores' => [
                    'kiwi-narvik' => 4290,
                    'coop-extra-narvik' => 4390,
                    'bunnpris-narvik' => 4590,
                ],
            ],
            [
                'name' => 'Arctic Apples 1kg',
                'slug' => 'arctic-apples-1kg',
                'description' => 'Местные сорта яблок, 1 кг.',
                'stores' => [
                    'rema-1000-narvik' => 2990,
                    'kiwi-narvik' => 3090,
                    'coop-extra-narvik' => 3290,
                ],
            ],
            [
                'name' => 'Fjord Ground Coffee 250g',
                'slug' => 'fjord-ground-coffee-250g',
                'description' => 'Скандинавская обжарка среднего уровня, 250 грамм.',
                'stores' => [
                    'coop-prix-narvik' => 5490,
                    'eurospar-narvik' => 5690,
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'is_active' => true,
                ]
            );

            foreach ($productData['stores'] as $storeSlug => $price) {
                $store = Store::where('slug', $storeSlug)->first();

                if (! $store) {
                    continue;
                }

                ProductStorePrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                    ],
                    [
                        'price' => $price,
                    ]
                );
            }
        }
    }
}
