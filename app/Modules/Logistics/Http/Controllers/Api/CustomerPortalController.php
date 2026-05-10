<?php

namespace App\Modules\Logistics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function shipments(Request $request): JsonResponse
    {
        $shipments = Shipment::query()
            ->where(function ($query) use ($request) {
                $query
                    ->where('sender_user_id', $request->user()?->id)
                    ->orWhere('recipient_user_id', $request->user()?->id);
            })
            ->latest('id')
            ->paginate(20);

        return response()->json($shipments);
    }
}

