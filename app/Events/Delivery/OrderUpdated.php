<?php

namespace App\Events\Delivery;

use App\Models\Delivery\DeliveryOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public DeliveryOrder $deliveryOrder
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.'.$this->deliveryOrder->order_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->deliveryOrder->order_id,
            'delivery_order_id' => $this->deliveryOrder->id,
            'tracking_status' => $this->deliveryOrder->tracking_status,
            'eta' => $this->deliveryOrder->eta?->toIso8601String(),
            'courier_location' => $this->deliveryOrder->courier_location,
            'updated_at' => $this->deliveryOrder->updated_at->toIso8601String(),
        ];
    }
}
