<?php

namespace App\Modules\Logistics\Livewire;

use Livewire\Component;

class ShipmentStatusBadge extends Component
{
    public string $status = 'created';

    public function render()
    {
        return view('livewire.logistics.shipment-status-badge');
    }
}
