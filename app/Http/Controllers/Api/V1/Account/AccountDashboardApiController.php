<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Presenters\OrderPresenter;
use App\Services\Account\AccountContextManager;
use App\Services\Account\AccountReadService;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\Http\JsonResponse;

class AccountDashboardApiController extends Controller
{
    public function show(
        AccountReadService $accountRead,
        CareAccountReadService $careRead,
        AccountContextManager $contextManager
    ): JsonResponse {
        $user = auth()->user();
        $activeClient = $contextManager->getActiveClient($user);

        $ordersPaginator = $accountRead->getPaginatedOrdersForUserAndClient(
            $user,
            $activeClient,
            null,
            null,
            5
        );

        $orderCards = $ordersPaginator->getCollection()
            ->map([OrderPresenter::class, 'forAccount'])
            ->values()
            ->all();

        $hasSocialCareAccess = $careRead->userHasAnyCareRelation($user);

        $upcomingCareVisits = $activeClient
            ? $careRead->getUpcomingVisitsForClient($activeClient, 3)
            : $careRead->getUpcomingVisitsForUser($user, 3);

        $kpi = $accountRead->getOrderKpiForUser($user);

        return response()->json([
            'data' => [
                'active_client' => $activeClient ? [
                    'id' => $activeClient->id,
                    'full_name' => $activeClient->full_name,
                    'city' => $activeClient->city,
                ] : null,
                'orders' => $orderCards,
                'has_social_care_access' => $hasSocialCareAccess,
                'upcoming_care_visits' => $upcomingCareVisits->map(function ($details) {
                    return [
                        'order_id' => $details->order_id,
                        'care_order_details_id' => $details->id,
                        'care_service_name' => $details->careService?->name,
                        'scheduled_start_at' => $details->scheduled_start_at,
                        'status' => (string) $details->care_status,
                    ];
                })->values()->all(),
                'kpi' => $kpi,
            ],
        ]);
    }
}
