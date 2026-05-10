<?php

namespace Database\Seeders;

use App\Models\Delivery\DeliveryOrder;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDeliveryOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo delivery orders...');

        // Create or find demo orders
        $order1 = Order::firstOrCreate(
            ['order_number' => 'DEMO-001'],
            ['total_amount' => 5900, 'status' => 'pending', 'user_id' => 1]
        );
        $order2 = Order::firstOrCreate(
            ['order_number' => 'DEMO-002'],
            ['total_amount' => 12500, 'status' => 'pending', 'user_id' => 1]
        );

        $orders = [
            [
                'order_id' => $order1->id,
                'courier_id' => null,
                'type' => 'grocery',
                'tracking_status' => 'pending',
                'tracking_token' => Str::uuid(),
                'pickup_location' => '68.4384, 17.4272',
                'delivery_location' => '68.4385, 17.4280',
                'is_urgent' => false,
                'estimated_distance_km' => 1.2,
                'estimated_duration_minutes' => 15,
            ],
            [
                'order_id' => $order2->id,
                'courier_id' => null,
                'type' => 'bulky',
                'tracking_status' => 'assigned',
                'tracking_token' => Str::uuid(),
                'pickup_location' => '68.4400, 17.4300',
                'delivery_location' => '68.4450, 17.4350',
                'is_urgent' => true,
                'estimated_distance_km' => 2.5,
                'estimated_duration_minutes' => 25,
            ],
        ];

        foreach ($orders as $o) {
            DeliveryOrder::updateOrCreate(
                ['order_id' => $o['order_id']],
                $o
            );
            $this->command->info("  ✓ Demo delivery order seeded: {$o['type']} ({$o['tracking_status']})");
        }

        $this->command->info('✅ Demo delivery orders seeded.');
    }
}
