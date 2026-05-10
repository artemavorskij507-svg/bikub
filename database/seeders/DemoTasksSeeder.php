<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\TaskGenerator;
use Illuminate\Database\Seeder;

class DemoTasksSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        // create few orders
        for ($i = 0; $i < 3; $i++) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-DEMO-'.now()->format('Ymd-His').'-'.$i,
                'status' => 'confirmed',
                'priority' => 'normal',
                'total_amount' => 0,
                'currency' => 'NOK',
                'payment_status' => 'pending',
                'metadata' => null,
            ]);
            app(TaskGenerator::class)->generateForOrder($order);
        }
    }
}
