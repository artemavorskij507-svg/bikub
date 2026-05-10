<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Task;
use App\Services\Operations\ServiceJobNormalizer;
use Illuminate\Console\Command;

class OperationsBackfillServiceJobs extends Command
{
    protected $signature = 'operations:backfill-service-jobs {--days=90} {--active-only=0}';

    protected $description = 'Backfill ServiceJob records from existing Orders and Tasks';

    public function handle(ServiceJobNormalizer $normalizer): int
    {
        $days = (int) $this->option('days');
        $activeOnly = (bool) $this->option('active-only');
        $from = now()->subDays($days);

        $orderQuery = Order::query();
        if ($activeOnly) {
            $orderQuery->whereNotIn('status', ['completed', 'cancelled', 'canceled']);
        } else {
            $orderQuery->where('created_at', '>=', $from)
                ->orWhereNotIn('status', ['completed', 'cancelled', 'canceled']);
        }

        $taskQuery = Task::query();
        if ($activeOnly) {
            $taskQuery->whereNotIn('status', ['completed', 'failed', 'canceled']);
        } else {
            $taskQuery->where('created_at', '>=', $from)
                ->orWhereNotIn('status', ['completed', 'failed', 'canceled']);
        }

        $orders = $orderQuery->cursor();
        $tasks = $taskQuery->cursor();

        $ordersCount = 0;
        foreach ($orders as $order) {
            $normalizer->normalizeFromOrder($order);
            $ordersCount++;
        }

        $tasksCount = 0;
        foreach ($tasks as $task) {
            $normalizer->normalizeFromTask($task);
            $tasksCount++;
        }

        $this->info("ServiceJob backfill done. Orders: {$ordersCount}, Tasks: {$tasksCount}");

        return self::SUCCESS;
    }
}

