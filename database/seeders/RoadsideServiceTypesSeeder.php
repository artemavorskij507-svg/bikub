<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class RoadsideServiceTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            [
                'code' => 'roadside_assistance',
                'name' => 'Помощь на дороге',
                'slug' => 'roadside-assistance',
                'description' => 'Помощь на дороге: прикуривание, замена колеса, подвоз топлива, диагностика',
                'category' => 'roadside_assistance',
                'icon' => 'heroicon-o-truck',
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'code' => 'vehicle_transport',
                'name' => 'Эвакуация',
                'slug' => 'vehicle-transport',
                'description' => 'Эвакуация и транспортировка автомобиля',
                'category' => 'vehicle_transport',
                'icon' => 'heroicon-o-truck',
                'is_active' => true,
                'sort_order' => 101,
            ],
            [
                'code' => 'vehicle_inspection',
                'name' => 'Осмотр авто',
                'slug' => 'vehicle-inspection',
                'description' => 'Предпокупочный, сервисный осмотр автомобиля',
                'category' => 'vehicle_inspection',
                'icon' => 'heroicon-o-clipboard-check',
                'is_active' => true,
                'sort_order' => 102,
            ],
        ];

        foreach ($serviceTypes as $serviceTypeData) {
            ServiceType::updateOrCreate(
                ['code' => $serviceTypeData['code']],
                $serviceTypeData
            );
        }

        $this->command->info('✅ Roadside service types created/updated');
    }
}
