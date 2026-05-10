<?php

namespace App\Modules\BikubeAssistant;

use App\Http\Controllers\Controller;
use App\Models\Order;

class AssistantController extends Controller
{
    public function insights(Order $order)
    {
        $assistant = new BikubeAssistantService;

        return response()->json([
            'order' => $order->id,
            'insights' => $assistant->generateInsights($order),
        ]);
    }
}
