<?php

namespace App\Modules\Logistics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\DeliveryPersonnel;
use App\Modules\Logistics\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerPortalController extends Controller
{
    public function assigned(Request $request): JsonResponse
    {
        $personnelId = DeliveryPersonnel::query()->where('user_id', $request->user()?->id)->value('id');
        $shipments = Shipment::query()
            ->where('assigned_personnel_id', $personnelId)
            ->active()
            ->latest('id')
            ->paginate(20);

        return response()->json($shipments);
    }
}

