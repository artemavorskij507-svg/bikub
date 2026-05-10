<?php

namespace Database\Seeders;

use App\Enums\DeliveryType;
use App\Models\Delivery\BulkyOrder;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\FoodOrder;
use App\Models\Delivery\GroceryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliveryOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Отримуємо або створюємо користувачів
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Створюємо замовлення для доставки
        $orders = [];
        for ($i = 0; $i < 10; $i++) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => ['pending', 'processing', 'completed', 'cancelled'][array_rand(['pending', 'processing', 'completed', 'cancelled'])],
                'total_amount' => rand(100, 5000) / 100,
                'currency' => 'NOK',
                'payment_status' => ['pending', 'paid', 'failed'][array_rand(['pending', 'paid', 'failed'])],
            ]);
            $orders[] = $order;
        }

        // Створюємо доставки продуктів
        for ($i = 0; $i < 4; $i++) {
            $order = $orders[$i];

            // Спочатку створюємо GroceryOrder
            $groceryOrder = GroceryOrder::create([
                'substitution_policy' => ['strict', 'ai', 'contact'][array_rand(['strict', 'ai', 'contact'])],
                'is_urgent' => rand(0, 1) === 1,
                'store_id' => null,
            ]);

            // Потім створюємо DeliveryOrder з поліморфним зв'язком
            DeliveryOrder::create([
                'order_id' => $order->id,
                'type' => DeliveryType::GROCERY,
                'orderable_type' => GroceryOrder::class,
                'orderable_id' => $groceryOrder->id,
                'pickup_address' => 'Narvik, Storgata '.rand(1, 50),
                'delivery_address' => 'Narvik, Kongens gate '.rand(1, 100),
                'pickup_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'delivery_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'estimated_distance_km' => rand(1, 20) + (rand(0, 99) / 100),
                'estimated_duration_minutes' => rand(15, 60),
                'eta' => now()->addMinutes(rand(30, 120)),
                'tracking_status' => ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'][array_rand(['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'])],
                'substitution_policy' => $groceryOrder->substitution_policy,
                'is_urgent' => $groceryOrder->is_urgent,
            ]);
        }

        // Створюємо доставки крупногабариту
        for ($i = 4; $i < 7; $i++) {
            $order = $orders[$i];

            // Спочатку створюємо BulkyOrder
            $bulkyOrder = BulkyOrder::create([
                'dimensions' => [
                    'length' => rand(50, 200),
                    'width' => rand(30, 150),
                    'height' => rand(20, 100),
                ],
                'weight_kg' => rand(10, 500),
                'services' => ['assembly', 'disassembly', 'packaging'][array_rand(['assembly', 'disassembly', 'packaging'])],
                'floor_number' => rand(1, 10),
                'elevator_available' => rand(0, 1) === 1,
            ]);

            // Потім створюємо DeliveryOrder з поліморфним зв'язком
            DeliveryOrder::create([
                'order_id' => $order->id,
                'type' => DeliveryType::BULKY,
                'orderable_type' => BulkyOrder::class,
                'orderable_id' => $bulkyOrder->id,
                'pickup_address' => 'Narvik, Storgata '.rand(1, 50),
                'delivery_address' => 'Narvik, Kongens gate '.rand(1, 100),
                'pickup_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'delivery_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'estimated_distance_km' => rand(1, 20) + (rand(0, 99) / 100),
                'estimated_duration_minutes' => rand(30, 90),
                'eta' => now()->addMinutes(rand(60, 180)),
                'tracking_status' => ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'][array_rand(['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'])],
                'is_urgent' => rand(0, 1) === 1,
            ]);
        }

        // Створюємо доставки їжі
        for ($i = 7; $i < 10; $i++) {
            $order = $orders[$i];

            // Спочатку створюємо FoodOrder
            $tempReq = ['hot', 'cold', 'ambient'][array_rand(['hot', 'cold', 'ambient'])];
            $foodOrder = FoodOrder::create([
                'restaurant_id' => null,
                'items' => [
                    ['name' => 'Pizza', 'quantity' => rand(1, 3), 'price' => rand(150, 300) / 100],
                    ['name' => 'Burger', 'quantity' => rand(1, 2), 'price' => rand(100, 200) / 100],
                ],
                'temperature_requirements' => [$tempReq => true],
            ]);

            // Потім створюємо DeliveryOrder з поліморфним зв'язком
            DeliveryOrder::create([
                'order_id' => $order->id,
                'type' => DeliveryType::FOOD,
                'orderable_type' => FoodOrder::class,
                'orderable_id' => $foodOrder->id,
                'pickup_address' => 'Narvik, Storgata '.rand(1, 50),
                'delivery_address' => 'Narvik, Kongens gate '.rand(1, 100),
                'pickup_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'delivery_location' => [
                    'lat' => 68.4384 + (rand(-100, 100) / 10000),
                    'lng' => 17.4273 + (rand(-100, 100) / 10000),
                ],
                'estimated_distance_km' => rand(1, 10) + (rand(0, 99) / 100),
                'estimated_duration_minutes' => rand(15, 45),
                'eta' => now()->addMinutes(rand(20, 60)),
                'tracking_status' => ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'][array_rand(['pending', 'assigned', 'picked_up', 'in_transit', 'delivered'])],
                'is_urgent' => rand(0, 1) === 1,
            ]);
        }

        $this->command->info('Created 10 delivery orders with different types.');
    }
}
