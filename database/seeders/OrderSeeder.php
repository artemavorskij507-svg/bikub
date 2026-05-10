<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PricingRule;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём тестовых пользователей если их нет
        $users = User::all();
        if ($users->isEmpty()) {
            $users = collect([
                User::create([
                    'name' => 'Іван Петренко',
                    'email' => 'ivan@example.com',
                    'password' => bcrypt('password'),
                ]),
                User::create([
                    'name' => 'Марія Коваленко',
                    'email' => 'maria@example.com',
                    'password' => bcrypt('password'),
                ]),
                User::create([
                    'name' => 'Олександр Шевченко',
                    'email' => 'olexander@example.com',
                    'password' => bcrypt('password'),
                ]),
            ]);
        }

        // Получаем услуги
        $delivery = ServiceType::where('slug', 'care-l1-med-delivery')->first();
        $bikeService = ServiceType::where('slug', 'master-basic-service')->first()
            ?? ServiceType::where('category', 'master')->first();
        $ecoPickup = ServiceType::where('slug', 'eco-l1-standard-pickup')->first()
            ?? ServiceType::where('category', 'eco')->first();
        $foodDelivery = ServiceType::where('slug', 'food-cafe-restaurant')->first();

        // Если услуги не найдены, используем первые доступные
        if (! $delivery) {
            $delivery = ServiceType::where('category', 'care')->first();
        }
        if (! $bikeService) {
            $bikeService = ServiceType::where('category', 'master')->first();
        }
        if (! $ecoPickup) {
            $ecoPickup = ServiceType::where('category', 'eco')->first();
        }
        if (! $foodDelivery) {
            $foodDelivery = ServiceType::where('category', 'food')->first();
        }

        // Создаём тестовые заказы
        $orders = [
            // Заказ 1: Доставка лекарств
            [
                'user' => $users->first(),
                'status' => 'completed',
                'priority' => 'high',
                'service' => $delivery,
                'notes' => 'Доставити ліки до адреси Kongens gate 25 до 14:00',
                'location' => [
                    'pickup' => ['address' => 'Аптека Narvik Apotek, Storgata 1', 'lat' => 68.4384, 'lng' => 17.4273],
                    'delivery' => ['address' => 'Kongens gate 25, Narvik', 'lat' => 68.4389, 'lng' => 17.4250],
                ],
                'scheduled_at' => now()->subDays(2)->setTime(13, 30),
                'started_at' => now()->subDays(2)->setTime(13, 35),
                'completed_at' => now()->subDays(2)->setTime(13, 55),
                'payment_status' => 'paid',
                'payment_method' => 'card',
            ],

            // Заказ 2: Починка велосипеда
            [
                'user' => $users->get(1),
                'status' => 'in_progress',
                'priority' => 'normal',
                'service' => $bikeService,
                'notes' => 'Прокачка гальм і заміна камери',
                'location' => [
                    'pickup' => ['address' => 'Torgsvingen 8, Narvik', 'lat' => 68.4350, 'lng' => 17.4150],
                    'delivery' => ['address' => 'Torgsvingen 8, Narvik', 'lat' => 68.4350, 'lng' => 17.4150],
                ],
                'scheduled_at' => now()->addHours(2),
                'started_at' => now()->subMinutes(30),
                'payment_status' => 'pending',
            ],

            // Заказ 3: Доставка еко-відходів
            [
                'user' => $users->first(),
                'status' => 'confirmed',
                'priority' => 'normal',
                'service' => $ecoPickup,
                'notes' => 'Забрати електроніку та батарейки',
                'location' => [
                    'pickup' => ['address' => 'Sjøveien 12, Narvik', 'lat' => 68.4400, 'lng' => 17.4220],
                    'delivery' => ['address' => 'Еко-центр Narvik Gjenbruksstasjon', 'lat' => 68.4450, 'lng' => 17.4300],
                ],
                'scheduled_at' => now()->addDays(1)->setTime(10, 0),
                'payment_status' => 'pending',
            ],

            // Заказ从未订购: Доставка ежі
            [
                'user' => $users->get(2),
                'status' => 'pending',
                'priority' => 'low',
                'service' => $foodDelivery,
                'notes' => 'Доставка кави та бутербродів на офіс',
                'location' => [
                    'pickup' => ['address' => 'Peppes Pizza, Storgata 14', 'lat' => 68.4375, 'lng' => 17.4275],
                    'delivery' => ['address' => 'Kongens gate 40, Narvik', 'lat' => 68.4395, 'lng' => 17.4255],
                ],
                'scheduled_at' => now()->addHours(1),
                'payment_status' => 'pending',
            ],

            // Заказ 5: Термінова доставка
            [
                'user' => $users->get(1),
                'status' => 'cancelled',
                'priority' => 'urgent',
                'service' => $delivery,
                'notes' => 'Скасовано клієнтом',
                'location' => [
                    'pickup' => ['address' => 'Аптека, Storgata 1', 'lat' => 68.4384, 'lng' => 17.4273],
                    'delivery' => ['address' => 'Nordstraumen 15', 'lat' => 68.4500, 'lng' => 17.4400],
                ],
                'scheduled_at' => now()->subHours(3),
                'payment_status' => 'pending',
            ],
        ];

        foreach ($orders as $orderData) {
            $service = $orderData['service'];
            if (! $service) {
                continue; // Skip if service is null
            }
            $pricingRule = PricingRule::where('service_type_id', $service->id)->first();

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $orderData['user']->id,
                'status' => $orderData['status'],
                'priority' => $orderData['priority'],
                'notes' => $orderData['notes'],
                'location' => $orderData['location'],
                'scheduled_at' => $orderData['scheduled_at'] ?? null,
                'started_at' => $orderData['started_at'] ?? null,
                'completed_at' => $orderData['completed_at'] ?? null,
                'total_amount' => $pricingRule->base_price ?? 0,
                'currency' => 'NOK',
                'payment_status' => $orderData['payment_status'],
                'payment_method' => $orderData['payment_method'] ?? null,
            ]);

            // Создаём позиции заказа
            OrderItem::create([
                'order_id' => $order->id,
                'service_type_id' => $service->id,
                'pricing_rule_id' => $pricingRule->id ?? null,
                'name' => $service->name,
                'description' => $service->description,
                'quantity' => 1,
                'unit_price' => $pricingRule->base_price ?? 0,
                'total_price' => $pricingRule->base_price ?? 0,
                'currency' => 'NOK',
            ]);
        }

        $this->command->info('✅ Створено 5 тестових замовлень!');
    }
}
