<?php

namespace App\Listeners\Notifications;

use App\Events\OrderCanceled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Services\Notifications\NotificationFeedService;

class PushOrderEventToFeed
{
    public function __construct(
        protected NotificationFeedService $feed
    ) {}

    public function handle(OrderCreated|OrderPaid|OrderCompleted|OrderCanceled $event): void
    {
        $order = $event->order;

        if (! $order instanceof Order || ! $order->relationLoaded('user')) {
            $order->loadMissing('user');
        }

        $user = $order->user;

        if (! $user) {
            return;
        }

        [$type, $title] = $this->buildPayload($event, $order);

        $this->feed->push(
            $user,
            $type,
            'order',
            $title,
            null,
            $order,
            [
                'order_id' => $order->id,
                'status' => (string) $order->status,
                'service_type' => (string) $order->service_type,
            ]
        );
    }

    protected function buildPayload($event, Order $order): array
    {
        return match (true) {
            $event instanceof OrderCreated => ['order.created', "Создан заказ #{$order->id}"],
            $event instanceof OrderPaid => ['order.paid', "Заказ #{$order->id} оплачен"],
            $event instanceof OrderCompleted => ['order.completed', "Заказ #{$order->id} выполнен"],
            $event instanceof OrderCanceled => ['order.canceled', "Заказ #{$order->id} отменён"],
            default => ['order.event', "Обновление по заказу #{$order->id}"],
        };
    }
}
