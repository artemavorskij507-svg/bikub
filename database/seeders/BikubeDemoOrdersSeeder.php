<?php

namespace Database\Seeders;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Models\Delivery\BulkyOrder;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\FoodOrder;
use App\Models\Delivery\GroceryItem;
use App\Models\Delivery\GroceryOrder;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BikubeDemoOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Bikube Demo Orders...');

        // Получаем или создаем тестового пользователя
        $user = User::firstOrCreate(
            ['email' => 'demo@glf.no'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $store = RetailStore::where('supports_grocery_delivery', true)->first();
        $restaurant = Restaurant::where('supports_food_delivery', true)->first();

        // 1. Delivery - Grocery
        if ($store) {
            $groceryOrder = $this->createDeliveryOrder(
                $user,
                DeliveryType::GROCERY,
                [
                    'pickup_address' => $store->address ?? 'Store Address',
                    'delivery_address' => 'Test Street 1, Narvik',
                    'pickup_location' => ['lat' => 68.4372, 'lng' => 17.4289, 'address' => $store->address],
                    'delivery_location' => ['lat' => 68.4380, 'lng' => 17.4300, 'address' => 'Test Street 1, Narvik'],
                ],
                $store->id
            );
            $this->command->info("Created Grocery Delivery Order #{$groceryOrder->order_id}");
        }

        // 2. Delivery - Food
        if ($restaurant) {
            $foodOrder = $this->createDeliveryOrder(
                $user,
                DeliveryType::FOOD,
                [
                    'pickup_address' => $restaurant->address ?? 'Restaurant Address',
                    'delivery_address' => 'Test Street 2, Narvik',
                    'pickup_location' => ['lat' => 68.4372, 'lng' => 17.4289, 'address' => $restaurant->address],
                    'delivery_location' => ['lat' => 68.4390, 'lng' => 17.4310, 'address' => 'Test Street 2, Narvik'],
                ]
            );
            $this->command->info("Created Food Delivery Order #{$foodOrder->order_id}");
        }

        // 3. Delivery - Bulky
        $bulkyOrder = $this->createDeliveryOrder(
            $user,
            DeliveryType::BULKY,
            [
                'pickup_address' => 'Furniture Store, Narvik',
                'delivery_address' => 'Test Street 3, Narvik',
                'pickup_location' => ['lat' => 68.4370, 'lng' => 17.4280, 'address' => 'Furniture Store, Narvik'],
                'delivery_location' => ['lat' => 68.4400, 'lng' => 17.4320, 'address' => 'Test Street 3, Narvik'],
                'dimensions' => ['length' => 2, 'width' => 1, 'height' => 0.5],
            ]
        );
        $this->command->info("Created Bulky Delivery Order #{$bulkyOrder->order_id}");

        // 4. Handyman Order
        $handymanOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'confirmed',
            'service_type' => 'handyman',
            'total_amount' => 500.00,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'scheduled_at' => Carbon::now()->addHours(2),
            'metadata' => [
                'service' => 'Ремонт сантехники',
                'address' => 'Test Street 4, Narvik',
            ],
        ]);
        $this->command->info("Created Handyman Order #{$handymanOrder->id}");

        // 5. Eco Order
        $ecoOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'service_type' => 'eco',
            'total_amount' => 299.00,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'metadata' => [
                'service' => 'Вывоз мебели',
                'items' => ['Диван', 'Стол'],
            ],
        ]);
        $this->command->info("Created Eco Order #{$ecoOrder->id}");

        // 6. Social Care Order
        $socialOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'scheduled',
            'service_type' => 'social-care',
            'total_amount' => 0,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'scheduled_at' => Carbon::now()->addDays(1),
            'metadata' => [
                'service' => 'Визит помощника',
                'duration_minutes' => 60,
            ],
        ]);
        $this->command->info("Created Social Care Order #{$socialOrder->id}");

        // 7. Errand Order
        $errandOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'service_type' => 'errand',
            'total_amount' => 150.00,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'metadata' => [
                'category' => 'Покупки',
                'from_address' => 'Store Address',
                'to_address' => 'Test Street 5, Narvik',
            ],
        ]);
        $this->command->info("Created Errand Order #{$errandOrder->id}");

        $this->command->info('Bikube Demo Orders seeded successfully!');
    }

    protected function createDeliveryOrder(User $user, DeliveryType $type, array $data, ?int $storeId = null): DeliveryOrder
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'service_type' => 'delivery',
            'total_amount' => 199.00,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'location' => [
                'pickup' => $data['pickup_location'] ?? null,
                'delivery' => $data['delivery_location'] ?? null,
            ],
            'metadata' => [
                'delivery_type' => $type->value,
                'store_id' => $storeId,
            ],
        ]);

        $orderable = match ($type) {
            DeliveryType::GROCERY => $this->createGroceryOrderWithItems($storeId),
            DeliveryType::FOOD => FoodOrder::create([
                'restaurant_id' => $data['restaurant_id'] ?? null,
            ]),
            DeliveryType::BULKY => BulkyOrder::create([
                'dimensions' => json_encode($data['dimensions'] ?? ['length' => 1, 'width' => 1, 'height' => 1]),
                'weight_kg' => 50,
            ]),
        };

        $deliveryOrder = DeliveryOrder::create([
            'order_id' => $order->id,
            'type' => $type,
            'pickup_address' => $data['pickup_address'] ?? 'Pickup Address',
            'delivery_address' => $data['delivery_address'] ?? 'Delivery Address',
            'pickup_location' => $data['pickup_location'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'estimated_distance_km' => 2.5,
            'estimated_duration_minutes' => 15,
            'eta' => Carbon::now()->addMinutes(30),
            'tracking_status' => DeliveryTrackingStatus::PENDING,
            'orderable_type' => get_class($orderable),
            'orderable_id' => $orderable->id,
            'tracking_token' => \Illuminate\Support\Str::uuid(),
        ]);

        return $deliveryOrder;
    }

    protected function createGroceryOrderWithItems(?int $storeId): GroceryOrder
    {
        $groceryOrder = GroceryOrder::create([
            'store_id' => $storeId,
        ]);

        GroceryItem::create([
            'grocery_order_id' => $groceryOrder->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'total_price' => 100.00,
        ]);

        return $groceryOrder;
    }
}
