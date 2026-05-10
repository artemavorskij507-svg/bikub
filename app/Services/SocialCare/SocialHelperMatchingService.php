<?php

namespace App\Services\SocialCare;

use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialHelperMatchingService
{
    public function autoAssignHelperIfPossible(Order $order, ?User $assignedBy = null): ?Order
    {
        return DB::transaction(function () use ($order, $assignedBy) {
            $careDetails = $order->careDetails;

            if (! $careDetails) {
                return null;
            }

            if ($careDetails->assigned_helper_id) {
                return $order; // Already assigned
            }

            $clientProfile = $careDetails->clientProfile;
            if (! $clientProfile) {
                return null;
            }

            // Find suitable helper
            $helper = $this->findBestHelper($careDetails);

            if (! $helper) {
                Log::warning('No suitable helper found for care order', [
                    'order_id' => $order->id,
                    'client_profile_id' => $clientProfile->id,
                ]);

                return null;
            }

            $service = app(CareOrderService::class);

            return $service->assignHelper($order, $helper, $assignedBy);
        });
    }

    protected function findBestHelper(CareOrderDetails $careDetails): ?SocialHelperProfile
    {
        $query = SocialHelperProfile::query()
            ->where('is_active', true);

        // Filter by required level if specified
        if ($careDetails->careService && $careDetails->careService->required_level) {
            $query->where('level', $careDetails->careService->required_level);
        }

        // Filter by preferred helper if specified
        if ($careDetails->preferred_helper_id) {
            $preferred = SocialHelperProfile::find($careDetails->preferred_helper_id);
            if ($preferred && $preferred->is_active) {
                return $preferred;
            }
        }

        // Filter by requested level if specified
        if ($careDetails->requested_helper_level) {
            $query->where('level', $careDetails->requested_helper_level);
        }

        // Prefer helpers with police certificate for sensitive services
        if ($careDetails->careService && in_array($careDetails->careService->code ?? '', ['medication', 'medical'])) {
            $query->where('has_police_certificate', true)
                ->whereNotNull('police_certificate_verified_at');
        }

        // Order by rating and availability
        $helper = $query->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->first();

        return $helper;
    }
}
