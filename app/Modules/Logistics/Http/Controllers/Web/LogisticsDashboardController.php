<?php

namespace App\Modules\Logistics\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\Shipment;

class LogisticsDashboardController extends Controller
{
    public function __invoke()
    {
        return view('filament.pages.logistics-dashboard', [
            'activeShipments' => Shipment::query()->active()->count(),
        ]);
    }
}
