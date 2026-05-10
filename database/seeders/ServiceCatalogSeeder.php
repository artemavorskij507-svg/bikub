<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/service_catalogs.json')), true);

        DB::transaction(function () use ($data) {
            // Import categories
            foreach ($data['categories'] as $categoryData) {
                ServiceCategory::updateOrCreate(
                    ['code' => $categoryData['code']],
                    [
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'is_active' => true,
                    ]
                );
            }

            // Import service types
            foreach ($data['service_types'] as $serviceData) {
                $category = ServiceCategory::where('code', $serviceData['category'])->first();

                if (! $category) {
                    $this->command->warn("Category {$serviceData['category']} not found for service {$serviceData['code']}");

                    continue;
                }

                ServiceType::updateOrCreate(
                    ['code' => $serviceData['code']],
                    [
                        'service_category_id' => $category->id,
                        'name' => $serviceData['name'],
                        'slug' => \Str::slug($serviceData['name']),
                        'description' => $serviceData['description'],
                        'default_pricing' => $serviceData['default_pricing'],
                        'skills' => $serviceData['skills'] ?? [],
                        'inventory' => $serviceData['inventory'] ?? [],
                        'is_active' => true,
                    ]
                );
            }
        });

        $this->command->info('Service catalogs imported successfully!');
        $this->command->info('Categories: '.ServiceCategory::count());
        $this->command->info('Service Types: '.ServiceType::count());
    }
}
