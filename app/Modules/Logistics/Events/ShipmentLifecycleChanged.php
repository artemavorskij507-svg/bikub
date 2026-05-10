<?php

namespace App\Modules\Logistics\Events;

use App\Modules\Logistics\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentLifecycleChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly string $eventName,
        public readonly array $payload = []
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('logistics.shipments'),
            new Channel('logistics.shipments.' . $this->shipment->id),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'shipment_number' => $this->shipment->shipment_number,
            'status' => $this->shipment->status,
            'payload' => $this->payload,
        ];
    }
}

