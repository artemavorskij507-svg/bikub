<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use App\Models\VehicleInspectionRequest;

// TODO fixed by Cursor: debug controller for Roadside↔Orders linkage, used only for internal troubleshooting
class RoadsideOrdersDebugController extends Controller
{
    /**
     * Show debug information about Roadside ↔ Orders linkage.
     */
    public function index()
    {
        // Only allow in local environment
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        $roadsideEmergencies = RoadsideEmergency::with('order')->latest()->limit(10)->get();
        $inspectionRequests = VehicleInspectionRequest::with('order')->latest()->limit(10)->get();

        $roadsideServiceTypes = ServiceType::whereIn('code', [
            'roadside_assistance',
            'vehicle_transport',
            'vehicle_inspection',
        ])->get();

        $roadsideOrders = Order::where(function ($q) {
            $q->whereHas('orderItems.serviceType', function ($sq) {
                $sq->whereIn('code', ['roadside_assistance', 'vehicle_transport', 'vehicle_inspection']);
            })
                ->orWhereHas('roadsideDetails')
                ->orWhereHas('roadsideEmergency')
                ->orWhereHas('vehicleInspection');
        })
            ->with(['orderItems.serviceType', 'roadsideDetails', 'roadsideEmergency', 'vehicleInspection'])
            ->latest()
            ->limit(10)
            ->get();

        return view('debug.roadside-orders', [
            'stats' => [
                'roadside_emergencies_total' => RoadsideEmergency::count(),
                'roadside_emergencies_with_order' => RoadsideEmergency::whereNotNull('order_id')->count(),
                'roadside_emergencies_without_order' => RoadsideEmergency::whereNull('order_id')->count(),
                'inspection_requests_total' => VehicleInspectionRequest::count(),
                'inspection_requests_with_order' => VehicleInspectionRequest::whereNotNull('order_id')->count(),
                'inspection_requests_without_order' => VehicleInspectionRequest::whereNull('order_id')->count(),
                'roadside_orders_total' => Order::where(function ($q) {
                    $q->whereHas('orderItems.serviceType', function ($sq) {
                        $sq->whereIn('code', ['roadside_assistance', 'vehicle_transport', 'vehicle_inspection']);
                    })
                        ->orWhereHas('roadsideDetails')
                        ->orWhereHas('roadsideEmergency')
                        ->orWhereHas('vehicleInspection');
                })->count(),
            ],
            'roadside_emergencies' => $roadsideEmergencies,
            'inspection_requests' => $inspectionRequests,
            'roadside_orders' => $roadsideOrders,
            'service_types' => $roadsideServiceTypes,
        ]);
    }
}
