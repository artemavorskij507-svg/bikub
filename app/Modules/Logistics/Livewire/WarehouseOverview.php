<?php

namespace App\Modules\Logistics\Livewire;

use App\Modules\Logistics\Models\Warehouse;
use Livewire\Component;

class WarehouseOverview extends Component
{
    public function render()
    {
        return view('livewire.logistics.warehouse-overview', [
            'warehouses' => Warehouse::query()->with('zones')->get(),
        ]);
    }
}
