<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderAssignmentService;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderActionController extends Controller
{
    public function handle(Request $request, Order $order, OrderAssignmentService $assignments, OrderLifecycleService $lifecycle): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->canManageOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'You can manage only your assigned orders.'], 403);
        }

        $action = $this->normalizeAction((string) $request->input('action'));
        $reason = trim((string) $request->input('reason', ''));

        if ($action === null) {
            return response()->json(['success' => false, 'message' => 'Unsupported worker action.'], 422);
        }

        if (in_array($action, ['reject', 'report_problem'], true) && $reason === '') {
            return response()->json(['success' => false, 'message' => 'Reason is required for this action.'], 422);
        }

        try {
            $updatedOrder = DB::transaction(function () use ($order, $user, $action, $reason, $assignments, $lifecycle) {
                if ($action === 'accept') {
                    $assignments->assign($order, $user, $user->id, ['source' => 'lk.order.action']);
                    return $lifecycle->transition($order->fresh(), 'worker_accepted', $user->id, ['source' => 'lk.order.action']);
                }

                if ($action === 'reject') {
                    $order = $lifecycle->transition($order, 'waiting_dispatch', $user->id, [
                        'source' => 'lk.order.action',
                        'reason' => $reason,
                    ], true);
                    return $assignments->unassign($order, $user->id, [
                        'source' => 'lk.order.action',
                        'reason' => $reason,
                    ]);
                }

                if ($action === 'report_problem') {
                    return $lifecycle->transition($order, 'disputed', $user->id, [
                        'source' => 'lk.order.action',
                        'reason' => $reason,
                    ], true);
                }

                $targetStatus = [
                    'en_route' => 'worker_en_route',
                    'at_pickup' => 'at_pickup',
                    'picked_up' => 'picked_up',
                    'in_progress' => 'in_progress',
                    'arrived' => 'arrived',
                    'completed' => 'completed',
                    'cancel' => 'cancelled',
                ][$action] ?? null;

                if ($targetStatus === null) {
                    throw new \InvalidArgumentException('Unsupported worker action.');
                }

                if ($targetStatus === 'cancelled' && $reason === '') {
                    throw new \InvalidArgumentException('Cancel reason is required.');
                }

                return $lifecycle->transition($order, $targetStatus, $user->id, [
                    'source' => 'lk.order.action',
                    'reason' => $reason ?: null,
                ]);
            });

            return response()->json([
                'success' => true,
                'status' => $updatedOrder->status,
                'message' => 'Action applied successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('lk.order.action.failed', ['order_id' => $order->id, 'action' => $action, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function normalizeAction(string $action): ?string
    {
        $normalized = strtolower(trim($action));

        return [
            'accept' => 'accept',
            'reject' => 'reject',
            'start_travel' => 'en_route',
            'en_route' => 'en_route',
            'at_pickup' => 'at_pickup',
            'picked_up' => 'picked_up',
            'start_job' => 'in_progress',
            'in_progress' => 'in_progress',
            'arrived' => 'arrived',
            'finish_job' => 'completed',
            'completed' => 'completed',
            'cancel' => 'cancel',
            'report_problem' => 'report_problem',
        ][$normalized] ?? null;
    }

    private function canManageOrder($user, Order $order): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return (int) $order->assigned_to === (int) $user->id;
        }

        if ($user->hasAnyRole(['owner', 'admin', 'dispatcher', 'support', 'operator', 'ops_admin', 'ops_manager', 'ops_rules_admin'])) {
            return true;
        }

        if ($user->hasAnyRole(['worker', 'courier', 'executor', 'handyman'])) {
            return (int) $order->assigned_to === (int) $user->id;
        }

        // Fallback for legacy assigned executors without explicit worker roles.
        if ((int) $order->assigned_to === (int) $user->id && ! $user->hasRole('customer')) {
            return true;
        }

        return false;
    }
}
