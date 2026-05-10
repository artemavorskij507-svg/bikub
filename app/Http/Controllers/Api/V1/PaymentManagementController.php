<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentManagementController extends Controller
{
    private function authorizeFinanceAction(Request $request): void
    {
        $user = $request->user();
        abort_if(! $user, 403);
        if (method_exists($user, 'hasAnyRole')) {
            abort_unless($user->hasAnyRole(['owner', 'admin', 'dispatcher']), 403);
        }
    }

    public function manualReserve(Order $order, Request $request, PaymentEngine $engine): JsonResponse
    {
        $this->authorizeFinanceAction($request);
        $result = $engine->reserve($order, (string) $request->input('gateway', 'manual'));

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function manualCapture(Order $order, Request $request, PaymentEngine $engine): JsonResponse
    {
        $this->authorizeFinanceAction($request);
        $result = $engine->capture($order, (string) $request->input('gateway', 'manual'));

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function manualRefund(Order $order, Request $request, PaymentEngine $engine): JsonResponse
    {
        $this->authorizeFinanceAction($request);
        $result = $engine->refund($order, (string) $request->input('gateway', 'manual'));

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function vippsWebhook(Request $request): JsonResponse
    {
        // Safe mock webhook endpoint; real signature validation requires provider credentials.
        return response()->json([
            'success' => true,
            'message' => 'Webhook accepted in mock mode.',
            'received' => [
                'event' => $request->input('event'),
                'reference' => $request->input('reference'),
            ],
        ]);
    }
}
