<?php

namespace App\Services\Handyman;

use App\Models\Claim;
use App\Models\HandymanAssignment;
use App\Models\HandymanKpiSnapshot;
use App\Models\Moving\ExecutorProfile;
use App\Models\OrderReview;
use Illuminate\Support\Facades\DB;

class HandymanKpiService
{
    public function recalculateForExecutor(ExecutorProfile $executor): HandymanKpiSnapshot
    {
        return DB::transaction(function () use ($executor) {
            $assignments = HandymanAssignment::query()
                ->with('order.handymanDetails')
                ->where('executor_profile_id', $executor->id)
                ->get();

            $orders = $assignments
                ->pluck('order')
                ->filter()
                ->unique('id');

            $totalOrders = $orders->count();
            $completedOrders = $orders->where('status', 'completed')->count();
            $cancelledOrders = $orders->whereIn('status', ['cancelled', 'failed'])->count();

            $durations = $assignments->filter(function ($assignment) {
                return $assignment->actual_start_at && $assignment->actual_finish_at;
            })->map(function ($assignment) {
                return $assignment->actual_start_at->diffInMinutes($assignment->actual_finish_at);
            });

            $avgDuration = $durations->count() ? (int) round($durations->avg()) : 0;

            $onTimeCount = 0;
            foreach ($assignments as $assignment) {
                $order = $assignment->order;
                if (! $order || ! $assignment->actual_finish_at) {
                    continue;
                }

                $desiredFinish = $order->handymanDetails?->desired_finish_at
                    ?? $order->handymanDetails?->desired_start_at;

                if (! $desiredFinish) {
                    continue;
                }

                if ($assignment->actual_finish_at->lte($desiredFinish->copy()->addMinutes(30))) {
                    $onTimeCount++;
                }
            }

            $onTimeRate = $completedOrders > 0
                ? round(($onTimeCount / max(1, $completedOrders)) * 100, 2)
                : 0.0;

            $orderIds = $orders->pluck('id');

            $reviews = OrderReview::query()
                ->whereIn('order_id', $orderIds)
                ->where('executor_profile_id', $executor->id)
                ->get();

            $ratingsCount = $reviews->count();
            $avgRating = $ratingsCount ? round($reviews->avg('rating'), 2) : 0.0;

            $claims = Claim::query()
                ->whereIn('order_id', $orderIds)
                ->get();

            $claimsCount = $claims->count();
            $seriousClaimsCount = $claims->where('severity', 'high')->count();

            $clientIds = $orders->pluck('user_id')->filter()->values()->all();
            $uniqueClientsCount = count(array_unique($clientIds));
            $repeatClientsCount = collect(array_count_values($clientIds))
                ->filter(fn ($count) => $count > 1)
                ->sum();

            $qualityScore = $this->calculateQualityScore(
                $avgRating,
                $onTimeRate,
                $claimsCount,
                $seriousClaimsCount,
                $repeatClientsCount,
                $uniqueClientsCount
            );

            $snapshot = HandymanKpiSnapshot::updateOrCreate(
                ['executor_profile_id' => $executor->id],
                [
                    'calculated_at' => now(),
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'cancelled_orders' => $cancelledOrders,
                    'claims_count' => $claimsCount,
                    'serious_claims_count' => $seriousClaimsCount,
                    'avg_rating' => $avgRating,
                    'ratings_count' => $ratingsCount,
                    'on_time_rate' => $onTimeRate,
                    'avg_duration_minutes' => $avgDuration,
                    'repeat_clients_count' => $repeatClientsCount,
                    'unique_clients_count' => $uniqueClientsCount,
                    'quality_score' => $qualityScore,
                ]
            );

            $executor->forceFill([
                'rating' => $avgRating,
            ])->save();

            return $snapshot;
        });
    }

    protected function calculateQualityScore(
        float $avgRating,
        float $onTimeRate,
        int $claimsCount,
        int $seriousClaimsCount,
        int $repeatClientsCount,
        int $uniqueClientsCount
    ): int {
        $score = (int) round($avgRating * 20);
        $score += (int) round(min(20, $onTimeRate / 5));
        $score -= $claimsCount * 2;
        $score -= $seriousClaimsCount * 5;

        if ($uniqueClientsCount > 0) {
            $repeatRate = $repeatClientsCount / max(1, $uniqueClientsCount);
            $score += (int) round(min(10, $repeatRate * 10));
        }

        return max(0, min(150, $score));
    }
}
