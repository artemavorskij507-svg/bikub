<?php

namespace App\Modules\Logistics\Livewire;

use App\Modules\Logistics\Models\Shipment;
use Livewire\Component;

class DeliveryTimeline extends Component
{
    public int $shipmentId;

    public function render()
    {
        $shipment = Shipment::query()->with('trackingEvents')->find($this->shipmentId);
        return view('livewire.logistics.delivery-timeline', compact('shipment'));
    }
}
