<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Account\AccountContextManager;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\Http\JsonResponse;

class AccountSocialCareApiController extends Controller
{
    public function index(
        CareAccountReadService $careRead,
        AccountContextManager $contextManager
    ): JsonResponse {
        $user = auth()->user();

        $clients = $careRead->getClientsForUser($user);
        $activeClient = $contextManager->getActiveClient($user);

        $upcomingVisits = $activeClient
            ? $careRead->getUpcomingVisitsForClient($activeClient, 20)
            : $careRead->getUpcomingVisitsForUser($user, 20);

        $recentReports = $activeClient
            ? $careRead->getRecentReportsForClient($activeClient, 20)
            : $careRead->getRecentReportsForUser($user, 20);

        return response()->json([
            'data' => [
                'clients' => $clients->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'full_name' => $client->full_name,
                        'city' => $client->city,
                    ];
                })->values()->all(),
                'active_client' => $activeClient ? [
                    'id' => $activeClient->id,
                    'full_name' => $activeClient->full_name,
                    'city' => $activeClient->city,
                ] : null,
                'upcoming_visits' => $upcomingVisits->map(function ($details) {
                    return [
                        'order_id' => $details->order_id,
                        'care_order_details_id' => $details->id,
                        'care_service_name' => $details->careService?->name,
                        'scheduled_start_at' => $details->scheduled_start_at,
                        'status' => (string) $details->care_status,
                    ];
                })->values()->all(),
                'recent_reports' => $recentReports->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'created_at' => $report->created_at,
                        'summary' => $report->summary ?? null,
                        'care_order_details_id' => $report->careOrderDetails?->id,
                        'care_service_name' => $report->careOrderDetails?->careService?->name,
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function showVisit(
        Order $order,
        CareAccountReadService $careRead
    ): JsonResponse {
        $user = auth()->user();

        if (! $careRead->userCanAccessCareOrder($user, $order)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $order->load([
            'careDetails.clientProfile',
            'careDetails.trustedContact',
            'careDetails.careService',
            'careDetails.assignedHelper.user',
            'careDetails.visitReports',
        ]);

        $details = $order->careDetails;

        return response()->json([
            'data' => [
                'order_id' => $order->id,
                'service_type' => $order->service_type,
                'care_service' => $details->careService?->name,
                'client' => $details->clientProfile?->full_name,
                'trusted_contact' => $details->trustedContact?->full_name,
                'scheduled_start_at' => $details->scheduled_start_at,
                'status' => (string) $details->care_status,
                'helper' => $details->assignedHelper?->user?->name,
                'reports' => $details->visitReports->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'created_at' => $report->created_at,
                        'summary' => $report->summary ?? null,
                    ];
                })->values()->all(),
            ],
        ]);
    }
}
