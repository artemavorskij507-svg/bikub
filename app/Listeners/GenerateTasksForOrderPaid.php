<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\TaskGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateTasksForOrderPaid
{
    /**
     * Handle the OrderPaid event and generate tasks for the order.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            $order = $event->order;

            // Validate order has required data
            if (! $order->user_id) {
                Log::warning('Cannot generate tasks: order missing user_id', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            // Check if tasks already exist (prevent duplicates)
            if ($order->tasks()->count() > 0) {
                Log::info('Tasks already exist for this order - skipping generation', [
                    'order_id' => $order->id,
                    'existing_tasks' => $order->tasks()->count(),
                ]);

                return;
            }

            // Use transaction to ensure atomic operation
            DB::transaction(function () use ($order) {
                $generator = app(TaskGenerator::class);
                $generator->generateForOrder($order);

                Log::info('Tasks successfully generated for paid order', [
                    'order_id' => $order->id,
                    'tasks_created' => $order->tasks()->count(),
                ]);
            });

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found when generating tasks', [
                'order_id' => $event->order->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate tasks for paid order', [
                'order_id' => $event->order->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw - log and continue to prevent cascade failures
        }
    }
}
