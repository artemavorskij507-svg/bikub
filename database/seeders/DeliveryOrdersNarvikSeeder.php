<?php

namespace Database\Seeders;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\FoodOrder;
use App\Models\Delivery\GroceryItem;
use App\Models\Delivery\GroceryOrder;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeliveryOrdersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Delivery Orders...');

        // Get or create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@glf.no'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'phone' => '+47 12345678',
                'is_active' => true,
            ]
        );

        // Get Narvik zone
        $narvikZone = GeoZone::where('slug', 'narvik-center')
            ->orWhere('name', 'like', '%Narvik%')
            ->first();

        if (! $narvikZone) {
            $narvikZone = GeoZone::first();
        }

        // Get stores and restaurants
        $remaStore = RetailStore::where('name', 'Rema 1000 Narvik')->first();
        $coopStore = RetailStore::where('name', 'Coop Extra Fagerneset')->first();
        $peppesRestaurant = Restaurant::where('name', 'Peppes Pizza Narvik')->first();

        // Get couriers (executors)
        $couriers = \App\Models\Moving\ExecutorProfile::where('is_active', true)
            ->with('user')
            ->get();

        if ($couriers->isEmpty()) {
            $this->command->warn('  ⚠ No active couriers found. Please run CouriersNarvikSeeder first.');

            return;
        }

        // 1) Food delivery order
        if ($peppesRestaurant) {
            $this->createFoodOrder($user, $peppesRestaurant, $narvikZone, $couriers->random());
        }

        // 2) Grocery delivery order from Rema 1000
        if ($remaStore) {
            $this->createGroceryOrder($user, $remaStore, $narvikZone, $couriers->random(), 'Frydenlundgata 56, Narvik', 412.00, 'scheduled');
        }

        // 3) Grocery delivery order from Coop Extra
        if ($coopStore) {
            $this->createGroceryOrder($user, $coopStore, $narvikZone, $couriers->random(), 'Ankenesveien 25, Narvik', 233.00, 'pending');
        }

        $this->command->info('✅ Narvik Delivery Orders seeded successfully!');
    }

    private function createFoodOrder(User $user, Restaurant $restaurant, ?GeoZone $zone, $courier): void
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'service_type' => 'delivery_food',
            'status' => 'confirmed',
            'geo_zone_id' => $zone?->id,
            'total_amount' => 289.00,
            'currency' => 'NOK',
            'payment_status' => 'paid',
            'scheduled_at' => now()->addHours(1),
        ]);

        $foodOrder = FoodOrder::create([
            'restaurant_id' => $restaurant->id,
            'items' => [
                ['name' => 'Pepperoni Pizza', 'quantity' => 1, 'price' => 189],
                ['name' => 'Margherita', 'quantity' => 1, 'price' => 159],
            ],
            'special_instructions' => 'Extra spicy, please.',
        ]);

        $deliveryOrder = DeliveryOrder::create([
            'order_id' => $order->id,
            'type' => DeliveryType::FOOD,
            'pickup_address' => $restaurant->address,
            'delivery_address' => 'Kongens gate 24, Narvik',
            'estimated_distance_km' => 2.5,
            'estimated_duration_minutes' => 20,
            'eta' => now()->addHours(1)->addMinutes(20),
            'courier_id' => $courier->user_id,
            'tracking_status' => DeliveryTrackingStatus::IN_TRANSIT,
            'is_urgent' => false,
            'tracking_token' => (string) Str::uuid(),
            'orderable_type' => FoodOrder::class,
            'orderable_id' => $foodOrder->id,
        ]);

        $this->command->info("  ✓ Created food delivery order #{$order->order_number} from {$restaurant->name}");
    }

    private function createGroceryOrder(User $user, RetailStore $store, ?GeoZone $zone, $courier, string $deliveryAddress, float $totalPrice, string $status): void
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'service_type' => 'delivery_grocery',
            'status' => $status,
            'geo_zone_id' => $zone?->id,
            'total_amount' => $totalPrice,
            'currency' => 'NOK',
            'payment_status' => $status === 'scheduled' ? 'paid' : 'pending',
            'scheduled_at' => $status === 'scheduled' ? now()->addHours(2) : null,
        ]);

        $groceryOrder = GroceryOrder::create([
            'store_id' => $store->id,
            'substitution_policy' => SubstitutionPolicy::CONTACT,
            'is_urgent' => false,
            'notes' => 'Please pick fresh items.',
        ]);

        // Add grocery items
        $products = Product::where('is_active', true)->limit(3)->get();
        foreach ($products as $product) {
            GroceryItem::create([
                'grocery_order_id' => $groceryOrder->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 3),
                'unit_price' => rand(20, 120),
                'total_price' => rand(40, 360),
                'substitution_policy' => SubstitutionPolicy::CONTACT,
            ]);
        }

        $trackingStatus = match ($status) {
            'scheduled' => DeliveryTrackingStatus::ASSIGNED,
            'pending' => DeliveryTrackingStatus::PENDING,
            default => DeliveryTrackingStatus::PENDING,
        };

        $deliveryOrder = DeliveryOrder::create([
            'order_id' => $order->id,
            'type' => DeliveryType::GROCERY,
            'pickup_address' => $store->address,
            'delivery_address' => $deliveryAddress,
            'estimated_distance_km' => 3.5,
            'estimated_duration_minutes' => 25,
            'eta' => $status === 'scheduled' ? now()->addHours(2)->addMinutes(25) : null,
            'courier_id' => $status === 'scheduled' ? $courier->user_id : null,
            'tracking_status' => $trackingStatus,
            'is_urgent' => false,
            'tracking_token' => (string) Str::uuid(),
            'orderable_type' => GroceryOrder::class,
            'orderable_id' => $groceryOrder->id,
        ]);

        $this->command->info("  ✓ Created grocery delivery order #{$order->order_number} from {$store->name} (status: {$status})");
    }
}
