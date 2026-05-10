<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    /**
     * Track telemetry event
     */
    public function track(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'not_implemented',
            'message' => 'Telemetry tracking is not yet implemented',
        ], 501);
    }

    /**
     * Get telemetry data
     */
    public function getData(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'not_implemented',
            'message' => 'Telemetry data retrieval is not yet implemented',
        ], 501);
    }
}
