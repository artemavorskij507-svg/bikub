<?php

namespace App\Services\SocialCare;

use App\Events\SocialCare\CareOrderAssignedToHelper;
use App\Events\SocialCare\VisitReportSubmitted;
use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\VisitReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitLifecycleService
{
    private const FINAL_STATUSES = [
        'COMPLETED',
        'CANCELLED',
        'CANCELLED_BY_CLIENT',
        'CANCELLED_BY_OPERATOR',
        'CANCELLED_BY_TRUSTED_CONTACT',
        'NO_SHOW',
    ];

    public function helperAccept(Order $order, SocialHelperProfile $helper): Order
    {
        return DB::transaction(function () use ($order, $helper) {
            $careDetails = $order->careDetails;

            if (! $careDetails) {
                abort(404, 'Care order details not found');
            }

            // Allow self-assign if not assigned, or verify ownership if already assigned
            if ($careDetails->assigned_helper_id !== null && $careDetails->assigned_helper_id !== $helper->id) {
                abort(403, 'Visit already assigned to another helper');
            }

            if (! in_array($careDetails->care_status, ['SCHEDULED', 'PENDING'])) {
                abort(400, 'Visit cannot be accepted in current status: '.$careDetails->care_status);
            }

            $careDetails->update([
                'assigned_helper_id' => $helper->id,
                'care_status' => 'ACCEPTED_BY_HELPER',
            ]);

            $order->update([
                'assigned_to' => $helper->user_id,
            ]);

            Log::info('Helper accepted visit', [
                'order_id' => $order->id,
                'helper_id' => $helper->id,
            ]);

            // Dispatch event
            event(new CareOrderAssignedToHelper($order, $careDetails, $helper));

            return $order->fresh(['careDetails']);
        });
    }

    public function markEnRoute(Order $order, SocialHelperProfile $helper): Order
    {
        return DB::transaction(function () use ($order, $helper) {
            $careDetails = $order->careDetails;

            $this->ensureHelperOwnsVisit($careDetails, $helper);
            $this->ensureStatusTransition($careDetails->care_status, ['ACCEPTED_BY_HELPER', 'SCHEDULED'], 'EN_ROUTE');

            $careDetails->update([
                'care_status' => 'EN_ROUTE',
            ]);

            Log::info('Helper marked visit as en route', [
                'order_id' => $order->id,
                'helper_id' => $helper->id,
            ]);

            return $order->fresh(['careDetails']);
        });
    }

    public function startVisit(Order $order, SocialHelperProfile $helper, ?Carbon $startedAt = null): Order
    {
        return DB::transaction(function () use ($order, $helper, $startedAt) {
            $careDetails = $order->careDetails;

            $this->ensureHelperOwnsVisit($careDetails, $helper);
            $this->ensureStatusTransition($careDetails->care_status, ['ACCEPTED_BY_HELPER', 'EN_ROUTE', 'SCHEDULED'], 'IN_PROGRESS');

            $startedAt = $startedAt ?? now();

            $careDetails->update([
                'care_status' => 'IN_PROGRESS',
            ]);

            $order->update([
                'status' => 'in_progress',
                'started_at' => $startedAt,
            ]);

            Log::info('Helper started visit', [
                'order_id' => $order->id,
                'helper_id' => $helper->id,
                'started_at' => $startedAt,
            ]);

            return $order->fresh(['careDetails']);
        });
    }

    public function finishVisit(
        Order $order,
        SocialHelperProfile $helper,
        Carbon $endedAt,
        array $reportData
    ): Order {
        return DB::transaction(function () use ($order, $helper, $endedAt, $reportData) {
            $careDetails = $order->careDetails;

            $this->ensureHelperOwnsVisit($careDetails, $helper);
            $this->ensureStatusTransition($careDetails->care_status, ['IN_PROGRESS'], 'COMPLETED');

            $careDetails->update([
                'care_status' => 'COMPLETED',
            ]);

            $order->update([
                'status' => 'completed',
                'completed_at' => $endedAt,
            ]);

            $report = VisitReport::create([
                'care_order_details_id' => $careDetails->id,
                'helper_profile_id' => $helper->id,
                'started_at' => $reportData['started_at'] ?? $order->started_at ?? now(),
                'ended_at' => $endedAt,
                'status' => $reportData['status'] ?? 'COMPLETED',
                'summary' => $reportData['summary'],
                'client_mood' => $reportData['client_mood'] ?? null,
                'issues_noted' => $reportData['issues_noted'] ?? null,
                'followup_recommended' => $reportData['followup_recommended'] ?? false,
                'followup_notes' => $reportData['followup_notes'] ?? null,
            ]);

            // TODO: Award community points
            // TODO: Update helper rating

            Log::info('Helper finished visit', [
                'order_id' => $order->id,
                'helper_id' => $helper->id,
                'ended_at' => $endedAt,
            ]);

            // Dispatch event
            event(new VisitReportSubmitted($order, $careDetails, $report));

            return $order->fresh(['careDetails', 'careDetails.visitReports']);
        });
    }

    protected function ensureHelperOwnsVisit(CareOrderDetails $careDetails, SocialHelperProfile $helper): void
    {
        if ($careDetails->assigned_helper_id !== $helper->id) {
            abort(403, 'Visit not assigned to this helper');
        }
    }

    protected function ensureStatusTransition(string $currentStatus, array $allowedFrom, string $targetStatus): void
    {
        if (! in_array($currentStatus, $allowedFrom)) {
            abort(400, "Cannot transition from {$currentStatus} to {$targetStatus}");
        }
    }
}
