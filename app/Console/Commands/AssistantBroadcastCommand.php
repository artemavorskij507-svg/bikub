<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Modules\BikubeAssistant\BikubeAssistantService;
use Illuminate\Console\Command;

class AssistantBroadcastCommand extends Command
{
    protected $signature = 'assistant:broadcast';

    protected $description = 'Send smart assistant insights to couriers';

    public function handle()
    {
        $assistant = new BikubeAssistantService;

        // Расширяем поиск заказов для демонстрации
        $orders = Order::whereIn('status', ['in_progress', 'delivering', 'confirmed', 'assigned'])
            ->whereNotNull('assigned_to')
            ->with('assignedUser')
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('Нет активных заказов с назначенными курьерами.');

            return 0;
        }

        $sentCount = 0;
        foreach ($orders as $order) {
            try {
                $courier = $order->assignedUser;
                if (! $courier) {
                    $this->warn("Order #{$order->order_number} has no assigned courier");

                    continue;
                }

                $info = $assistant->generateInsights($order);
                $assistant->pushToCourier($courier, $info);

                $this->info("Sent assistant insights to courier {$courier->name} (ID: {$courier->id}) for order #{$order->order_number}");
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("Error processing order #{$order->order_number}: ".$e->getMessage());

                continue;
            }
        }

        $this->info("Successfully sent insights for {$sentCount} orders.");

        return 0;
    }
}
