<?php

namespace App\Services\Errand;

use App\Models\ErrandTask;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ErrandTaskService
{
    public function createForOrder(Order $order, array $data): ErrandTask
    {
        return DB::transaction(function () use ($order, $data) {
            /** @var ErrandTask $task */
            $task = ErrandTask::create(array_merge(
                $data,
                [
                    'order_id' => $order->id,
                    'status' => 'draft',
                ]
            ));

            // Сразу считаем предварительную цену по указанным параметрам
            $task->refreshPricing();

            return $task;
        });
    }

    public function recalculatePricing(ErrandTask $task): ErrandTask
    {
        return $task->refreshPricing();
    }
}
