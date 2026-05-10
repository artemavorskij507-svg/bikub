<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RetailStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductsNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Products and Menu Items...');

        // Products for stores
        $storeProducts = [
            ['store' => 'Rema 1000 Narvik', 'name' => 'Melk 1L', 'price' => 22.90, 'unit' => 'pcs'],
            ['store' => 'Rema 1000 Narvik', 'name' => 'Brød Grovt', 'price' => 29.90, 'unit' => 'pcs'],
            ['store' => 'Coop Extra Fagerneset', 'name' => 'Egg 12stk', 'price' => 39.00, 'unit' => 'box'],
            ['store' => 'Coop Extra Fagerneset', 'name' => 'Kyllingfilet 1kg', 'price' => 119.00, 'unit' => 'kg'],
            ['store' => 'Kiwi Ankenes', 'name' => 'Bananer', 'price' => 19.90, 'unit' => 'kg'],
        ];

        foreach ($storeProducts as $productData) {
            $store = RetailStore::where('name', $productData['store'])->first();
            if (! $store) {
                $this->command->warn("  ⚠ Store not found: {$productData['store']}");

                continue;
            }

            $slug = Str::slug($productData['name']);
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $productData['name'],
                    'description' => "Product from {$productData['store']}",
                    'sku' => 'PROD-'.strtoupper(Str::random(8)),
                    'is_active' => true,
                ]
            );

            // Note: ProductStorePrice uses Store model, not RetailStore
            // For now, we'll store product info in metadata
            $metadata = $store->metadata ?? [];
            if (! isset($metadata['products'])) {
                $metadata['products'] = [];
            }
            $metadata['products'][] = [
                'product_id' => $product->id,
                'name' => $productData['name'],
                'price' => $productData['price'],
                'unit' => $productData['unit'],
            ];
            $store->metadata = $metadata;
            $store->save();

            $this->command->info("  ✓ Created/Updated product: {$productData['name']} for {$productData['store']}");
        }

        // Menu items for restaurants (stored as metadata in Restaurant model)
        $restaurantMenuItems = [
            ['restaurant' => 'Peppes Pizza Narvik', 'name' => 'Pepperoni Pizza', 'price' => 189],
            ['restaurant' => 'Peppes Pizza Narvik', 'name' => 'Margherita', 'price' => 159],
            ['restaurant' => 'Asia House Narvik', 'name' => 'Chicken Wok', 'price' => 179],
            ['restaurant' => 'Asia House Narvik', 'name' => 'Sushi Mix 12stk', 'price' => 189],
        ];

        foreach ($restaurantMenuItems as $menuItem) {
            $restaurant = Restaurant::where('name', $menuItem['restaurant'])->first();
            if (! $restaurant) {
                $this->command->warn("  ⚠ Restaurant not found: {$menuItem['restaurant']}");

                continue;
            }

            // Store menu items in metadata
            $metadata = $restaurant->metadata ?? [];
            if (! isset($metadata['menu_items'])) {
                $metadata['menu_items'] = [];
            }
            $metadata['menu_items'][] = [
                'name' => $menuItem['name'],
                'price' => $menuItem['price'],
            ];
            $restaurant->metadata = $metadata;
            $restaurant->save();

            $this->command->info("  ✓ Added menu item: {$menuItem['name']} to {$menuItem['restaurant']}");
        }

        $this->command->info('✅ Narvik Products and Menu Items seeded successfully!');
    }
}
