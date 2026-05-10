<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderScenarioRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderTrackerController extends Controller
{
    public function show(Order $order, OrderScenarioRegistry $registry): View
    {
        $this->authorizeView($order);

        $scenario = null;
        try {
            $scenario = $registry->get((string) $order->service_type);
        } catch (\Throwable) {
            $scenario = null;
        }

        return view('orders.tracker', [
            'order' => $order->load(['assignedUser', 'events']),
            'scenario' => $scenario,
        ]);
    }

    public function api(Order $order, OrderScenarioRegistry $registry): JsonResponse
    {
        $this->authorizeView($order);

        $scenario = null;
        try {
            $scenario = $registry->publicMetadata($registry->get((string) $order->service_type));
        } catch (\Throwable) {
            $scenario = null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'eta' => $order->scheduled_at,
                'assigned_worker' => $order->assignedUser?->only(['id', 'name']),
                'scenario' => $scenario,
                'events' => $order->events()->limit(50)->get(['id', 'event_type', 'from_status', 'to_status', 'payload', 'created_at']),
            ],
        ]);
    }

    private function authorizeView(Order $order): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $isOwner = (int) $order->user_id === (int) $user->id;
        $isAssigned = (int) $order->assigned_to === (int) $user->id;
        $isAdminLike = method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'owner',
            'admin',
            'support',
            'operator',
            'ops_admin',
            'ops_manager',
            'ops_rules_admin',
            'dispatcher',
        ]);

        if (! $isOwner && ! $isAssigned && ! $isAdminLike) {
            abort(403);
        }
    }
}
