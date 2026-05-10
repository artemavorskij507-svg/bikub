<?php

namespace Database\Seeders;

use App\Models\RetailStore;
use Illuminate\Database\Seeder;

class RetailStoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGroceryStores();
        $this->seedDIYStores();
        $this->seedFurnitureStores();
    }

    private function seedGroceryStores(): void
    {
        $stores = [
            ['Bunnpris & Gourmet (Narvik)', 'bunnpris-gourmet-narvik', 'Grocery store', 'grocery', 'Bunnpris'],
            ['REMA 1000', 'rema-1000-narvik', 'Grocery store', 'grocery', 'REMA 1000'],
            ['Coop Extra', 'coop-extra-narvik', 'Grocery store', 'grocery', 'Coop'],
            ['Joker Narvik', 'joker-narvik', 'Grocery store', 'grocery', 'Joker'],
            ['SPAR Narvik', 'spar-narvik', 'Grocery store', 'grocery', 'SPAR'],
            ['Alicofood Noor Tawfiq Sabiri', 'alicofood-narvik', 'International food store', 'grocery', 'Alicofood', 'Håreksgate 77B, 8514 Narvik'],
            ['International Matvarer', 'international-matvarer', 'International food store', 'grocery', 'International Matvarer'],
            ['Sham Asia Matvarer', 'sham-asia-matvarer', 'Asian food store', 'grocery', 'Sham Asia Matvarer'],
        ];

        foreach ($stores as $store) {
            RetailStore::updateOrCreate(
                ['slug' => $store[1]],
                [
                    'name' => $store[0],
                    'description' => $store[2],
                    'category' => $store[3],
                    'chain_name' => $store[4],
                    'address' => $store[5] ?? 'Narvik, Norway',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'has_home_delivery' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDIYStores(): void
    {
        $stores = [
            ['Rusta', 'rusta-narvik', 'DIY and home goods', 'diy', 'Rusta'],
            ['Europris', 'europris-narvik', 'Discount store', 'diy', 'Europris'],
            ['Clas Ohlson', 'clas-ohlson-narvik', 'Tools and gadgets', 'diy', 'Clas Ohlson'],
            ['Biltema', 'biltema-narvik', 'Auto parts and tools', 'diy', 'Biltema'],
            ['Nille', 'nille-narvik', 'Craft and hobby', 'diy', 'Nille'],
            ['Obs BYGG', 'obs-bygg-narvik', 'Building materials', 'diy', 'Obs BYGG'],
            ['Monter', 'monter-narvik', 'Building materials', 'diy', 'Monter'],
            ['Byggerns Bjerkvik', 'byggerns-bjerkvik', 'Building materials', 'diy', 'Byggerns'],
            ['Byggtorget Mathisen & Mathisen', 'byggtorget-mathisen', 'Building materials', 'diy', 'Byggtorget'],
            ['Byggeriet S. Thorstensen', 'byggeriet-thorstensen', 'Building materials', 'diy', 'Byggeriet'],
            ['Felleskjøpet', 'felleskjopet-narvik', 'Agricultural supplies', 'diy', 'Felleskjøpet'],
        ];

        foreach ($stores as $store) {
            RetailStore::updateOrCreate(
                ['slug' => $store[1]],
                [
                    'name' => $store[0],
                    'description' => $store[2],
                    'category' => $store[3],
                    'chain_name' => $store[4],
                    'address' => 'Narvik, Norway',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'has_home_delivery' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedFurnitureStores(): void
    {
        $stores = [
            ['Elkjøp Narvik', 'elkjop-narvik', 'Electronics and appliances', 'electronics', 'Elkjøp'],
            ['POWER Narvik', 'power-narvik', 'Electronics and appliances', 'electronics', 'POWER'],
            ['Telenorbutikken AMFI Narvik', 'telenor-amfi', 'Mobile phones', 'electronics', 'Telenor'],
            ['AMFI Narvik', 'amfi-narvik', 'Shopping center', 'shopping-center', 'AMFI'],
            ['Soundgarden Narvik', 'soundgarden-narvik', 'Music and entertainment', 'electronics', 'Soundgarden'],
            ['Skeidar Narvik', 'skeidar-narvik', 'Furniture and interior', 'furniture', 'Skeidar'],
            ['Bohus Narvik', 'bohus-narvik', 'Furniture and interior', 'furniture', 'Bohus'],
            ['JYSK Narvik', 'jysk-narvik', 'Bed and home', 'furniture', 'JYSK'],
            ['Søstrene Grene Narvik', 'sostrene-grene-narvik', 'Home decor', 'furniture', 'Søstrene Grene'],
            ['Frydenlund Kontorspar Narvik', 'frydenlund-kontorspar', 'Office supplies', 'office', 'Frydenlund Kontorspar'],
            ['Tl Services A/S Narvik', 'tl-services-narvik', 'Services', 'services', 'Tl Services'],
            ['Clas Ohlson Narvik', 'clas-ohlson-narvik-2', 'Tools and gadgets', 'diy', 'Clas Ohlson'],
            ['TV-Tormod', 'tv-tormod', 'TV and electronics', 'electronics', 'TV-Tormod'],
            ['Byggtorget Mathisen & Mathisen', 'byggtorget-mm', 'Building materials', 'diy', 'Byggtorget'],
        ];

        foreach ($stores as $store) {
            RetailStore::updateOrCreate(
                ['slug' => $store[1]],
                [
                    'name' => $store[0],
                    'description' => $store[2],
                    'category' => $store[3],
                    'chain_name' => $store[4],
                    'address' => 'Narvik, Norway',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'has_home_delivery' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
