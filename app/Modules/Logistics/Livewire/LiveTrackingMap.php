<?php

namespace App\Modules\Logistics\Livewire;

use App\Modules\Logistics\Models\DeliveryPersonnel;
use Livewire\Component;

class LiveTrackingMap extends Component
{
    public function render()
    {
        return view('livewire.logistics.live-tracking-map', [
            'personnel' => DeliveryPersonnel::query()->limit(200)->get(),
        ]);
    }
}
