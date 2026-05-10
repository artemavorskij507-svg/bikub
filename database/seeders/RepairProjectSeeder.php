<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\RepairProject;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Установка стиральной машины',
                'description' => 'Подключение стиральной машины, проверка герметичности, тестовое включение',
                'base_price' => 899,
                'estimated_time' => '45-90 минут',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Сборка мебели IKEA / Jysk',
                'description' => 'Сборка шкафов, кроватей, комодов, столов, установка фурнитуры',
                'base_price' => 499,
                'estimated_time' => '1-3 часа',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Установка кухонных приборов',
                'description' => 'Подключение посудомойки, варочной панели, духового шкафа',
                'base_price' => 1299,
                'estimated_time' => '1-2 часа',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Мелкий бытовой ремонт',
                'description' => 'Карнизы, полки, замки, дверные ручки, простые сантехнические работы',
                'base_price' => 599,
                'estimated_time' => '30-90 минут',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Установка светильников и ламп',
                'description' => 'Монтаж потолочных и настенных светильников, LED-панелей',
                'base_price' => 699,
                'estimated_time' => '30-60 минут',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Разборка мебели перед переездом',
                'description' => 'Разборка шкафов, кроватей, письменных столов с сохранением крепежа',
                'base_price' => 799,
                'estimated_time' => '40-120 минут',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
            [
                'title' => 'Ремонт и обслуживание дверей',
                'description' => 'Регулировка дверей, замена петель, исправление скрипов и перекосов',
                'base_price' => 499,
                'estimated_time' => '30-60 минут',
                'region' => 'Narvik +60km',
                'status' => 'active',
            ],
        ];

        $user = User::first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Repair Demo User',
                'email' => 'repair-demo@glf.no',
                'password' => bcrypt('password'),
            ]);
        }

        $clientProfileId = ClientProfile::query()->value('id');

        foreach ($projects as $projectData) {
            $order = Order::factory()
                ->for($user, 'user')
                ->create([
                    'service_type' => 'handyman_project',
                    'status' => 'pending',
                    'notes' => $projectData['description'],
                ]);

            RepairProject::updateOrCreate(
                ['title' => $projectData['title']],
                [
                    'order_id' => $order->id,
                    'client_profile_id' => $clientProfileId,
                    'description' => $projectData['description'],
                    'status' => $projectData['status'],
                    'base_price' => $projectData['base_price'],
                    'estimated_time' => $projectData['estimated_time'],
                    'region' => $projectData['region'],
                    'address_line' => 'Narvik',
                    'city' => 'Narvik',
                    'notes' => 'Создано сидером RepairProjectSeeder.',
                ]
            );
        }
    }
}
