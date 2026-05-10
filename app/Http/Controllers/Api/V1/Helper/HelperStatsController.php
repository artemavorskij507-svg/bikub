<?php

namespace App\Http\Controllers\Api\V1\Helper;

use App\Http\Controllers\Api\V1\Helper\Concerns\ResolvesHelper;
use App\Http\Controllers\Controller;
use App\Models\CareOrderDetails;
use App\Models\CommunityPointsBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelperStatsController extends Controller
{
    use ResolvesHelper;

    public function stats(Request $request): JsonResponse
    {
        $helper = $this->helperProfile();

        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();

        $baseQuery = CareOrderDetails::query()
            ->where('assigned_helper_id', $helper->id)
            ->where('care_status', 'COMPLETED');

        $todayCount = (clone $baseQuery)
            ->whereBetween('scheduled_start_at', [$startOfDay, $now])
            ->count();

        $weekCount = (clone $baseQuery)
            ->whereBetween('scheduled_start_at', [$startOfWeek, $now])
            ->count();

        $totalCount = (clone $baseQuery)->count();

        $pointsBalance = CommunityPointsBalance::where('helper_profile_id', $helper->id)->first();

        return response()->json([
            'data' => [
                'visits_today' => $todayCount,
                'visits_this_week' => $weekCount,
                'visits_total' => $totalCount,
                'rating_avg' => $helper->rating_avg,
                'rating_count' => $helper->rating_count,
                'points_balance' => $pointsBalance?->balance_points ?? 0,
                'points_lifetime' => $pointsBalance?->lifetime_points ?? 0,
            ],
        ]);
    }
}
