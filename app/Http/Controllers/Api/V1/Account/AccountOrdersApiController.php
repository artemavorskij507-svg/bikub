<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Presenters\OrderPresenter;
use App\Services\Account\AccountContextManager;
use App\Services\Account\AccountReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountOrdersApiController extends Controller
{
    public function index(
        Request $request,
        AccountReadService $accountRead,
        AccountContextManager $contextManager
    ): JsonResponse {
        $user = auth()->user();
        $activeClient = $contextManager->getActiveClient($user);

        $serviceType = $request->query('type');
        $status = $request->query('status');

        $paginator = $accountRead->getPaginatedOrdersForUserAndClient(
            $user,
            $activeClient,
            $serviceType,
            $status
        );

        $items = $paginator->getCollection()
            ->map([OrderPresenter::class, 'forAccount'])
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(
        Order $order,
        AccountReadService $accountRead
    ): JsonResponse {
        $user = auth()->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $order->load([
            'subOrders',
            'parentOrder',
            'careContext.clientProfile',
            'careContext.trustedContact',
            'careDetails.careService',
            'careDetails.clientProfile',
            'careDetails.trustedContact',
            'careDetails.visitReports',
        ]);

        $presented = OrderPresenter::forAccount($order);

        $careContext = $order->careContext ? [
            'client' => $order->careContext->clientProfile?->full_name,
            'trusted_contact' => $order->careContext->trustedContact?->full_name,
            'notes_for_performer' => $order->careContext->notes_for_performer,
        ] : null;

        $careDetails = $order->careDetails ? [
            'care_service' => $order->careDetails->careService?->name,
            'client' => $order->careDetails->clientProfile?->full_name,
            'trusted_contact' => $order->careDetails->trustedContact?->full_name,
            'scheduled_start_at' => $order->careDetails->scheduled_start_at,
            'status' => (string) $order->careDetails->care_status,
            'reports' => $order->careDetails->visitReports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'created_at' => $report->created_at,
                    'summary' => $report->summary ?? null,
                ];
            })->values()->all(),
        ] : null;

        return response()->json([
            'data' => [
                'order' => $presented,
                'metadata' => $order->metadata,
                'notes' => $order->notes,
                'care_context' => $careContext,
                'care_details' => $careDetails,
                'parent_order_id' => $order->parent_order_id,
                'sub_orders' => $order->subOrders->pluck('id')->values()->all(),
            ],
        ]);
    }
}
