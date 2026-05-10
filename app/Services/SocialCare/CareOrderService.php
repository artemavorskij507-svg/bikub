<?php

namespace App\Services\SocialCare;

use App\Enums\CareOrderStatus;
use App\Events\SocialCare\CareOrderAssignedToHelper;
use App\Events\SocialCare\CareOrderStatusChanged;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CareOrderService
{
    public function assignHelper(Order $order, SocialHelperProfile $helper, ?User $assignedBy = null): Order
    {
        return DB::transaction(function () use ($order, $helper, $assignedBy) {
            $careDetails = $order->careDetails;

            if (! $careDetails) {
                throw new \InvalidArgumentException('Care order details not found');
            }

            $careDetails->update([
                'assigned_helper_id' => $helper->id,
            ]);

            if ($careDetails->care_status === CareOrderStatus::PENDING->value ||
                $careDetails->care_status === CareOrderStatus::SCHEDULED->value) {
                $careDetails->update([
                    'care_status' => CareOrderStatus::SCHEDULED->value,
                ]);
            }

            $order->update([
                'assigned_to' => $helper->user_id,
            ]);

            Log::info('Helper assigned to care order', [
                'order_id' => $order->id,
                'helper_id' => $helper->id,
                'assigned_by' => $assignedBy?->id,
            ]);

            // Dispatch event
            event(new CareOrderAssignedToHelper($order, $careDetails, $helper));

            return $order->fresh(['careDetails']);
        });
    }

    public function updateStatus(
        Order $order,
        CareOrderStatus $status,
        ?User $updatedBy = null,
        ?string $reason = null
    ): Order {
        return DB::transaction(function () use ($order, $status, $updatedBy, $reason) {
            $careDetails = $order->careDetails;

            if (! $careDetails) {
                throw new \InvalidArgumentException('Care order details not found');
            }

            $oldStatusValue = $careDetails->care_status;
            $oldStatus = CareOrderStatus::from($oldStatusValue);

            $careDetails->update([
                'care_status' => $status->value,
            ]);

            // Update order status if needed
            if ($status === CareOrderStatus::COMPLETED) {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            } elseif (in_array($status, [
                CareOrderStatus::CANCELLED,
                CareOrderStatus::CANCELLED_BY_CLIENT,
                CareOrderStatus::CANCELLED_BY_OPERATOR,
                CareOrderStatus::CANCELLED_BY_TRUSTED_CONTACT,
            ])) {
                $order->update([
                    'status' => 'cancelled',
                ]);
            }

            if ($reason) {
                $careDetails->update([
                    'internal_notes' => ($careDetails->internal_notes ? $careDetails->internal_notes."\n" : '').
                        now()->format('Y-m-d H:i').' ['.($updatedBy?->name ?? 'System').']: '.$reason,
                ]);
            }

            Log::info('Care order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatusValue,
                'new_status' => $status->value,
                'updated_by' => $updatedBy?->id,
            ]);

            // Dispatch event
            event(new CareOrderStatusChanged($order, $careDetails, $oldStatus, $status, $reason));

            return $order->fresh(['careDetails']);
        });
    }
}
