<?php

namespace App\Modules\Logistics\Events;

use App\Modules\Logistics\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?Shipment $shipment,
        public readonly array $payload = []
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new Channel('logistics.couriers')];

        if ($this->shipment) {
            $channels[] = new Channel('logistics.shipments.' . $this->shipment->id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'courier.location.updated';
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'shipment_id' => $this->shipment?->id,
        ], $this->payload);
    }
}

