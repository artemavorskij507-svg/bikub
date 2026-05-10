<?php

namespace App\Services\SocialCare;

use App\Enums\CareOrderStatus;
use App\Enums\ServiceType;
use App\Models\CareOrderDetails;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\TrustedContact;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class CareAccountReadService
{
    private function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает клиентские профили, связанные с пользователем.
     */
    public function getClientsForUser(User $user): Collection
    {
        if (! $this->hasTables(['client_profiles', 'trusted_contacts'])) {
            return collect();
        }

        return ClientProfile::query()
            ->where('user_id', $user->id)
            ->orWhereHas('trustedContacts', fn ($q) => $q->where('user_id', $user->id))
            ->with('trustedContacts')
            ->get()
            ->unique('id');
    }

    /**
     * Есть ли у пользователя доступ к Social Care.
     */
    public function userHasAnyCareRelation(User $user): bool
    {
        return $this->getClientsForUser($user)->isNotEmpty();
    }

    public function getUpcomingVisitsForUser(User $user, int $limit = 5): Collection
    {
        if (! $this->hasTables(['care_order_details'])) {
            return collect();
        }

        $clients = $this->getClientsForUser($user);

        if ($clients->isEmpty()) {
            return collect();
        }

        $clientIds = $clients->pluck('id');

        return CareOrderDetails::query()
            ->whereIn('client_profile_id', $clientIds)
            ->whereIn('care_status', $this->activeStatuses())
            ->with([
                'order',
                'careService',
                'clientProfile',
                'assignedHelper.user',
            ])
            ->orderBy('scheduled_start_at')
            ->limit($limit)
            ->get();
    }

    public function getUpcomingVisitsForClient(ClientProfile $client, int $limit = 10): Collection
    {
        if (! $this->hasTables(['care_order_details'])) {
            return collect();
        }

        return CareOrderDetails::query()
            ->where('client_profile_id', $client->id)
            ->whereIn('care_status', $this->activeStatuses())
            ->with([
                'order',
                'careService',
                'clientProfile',
                'assignedHelper.user',
            ])
            ->orderBy('scheduled_start_at')
            ->limit($limit)
            ->get();
    }

    public function getRecentReportsForUser(User $user, int $limit = 10): Collection
    {
        if (! $this->hasTables(['visit_reports', 'care_order_details'])) {
            return collect();
        }

        $clients = $this->getClientsForUser($user);

        if ($clients->isEmpty()) {
            return collect();
        }

        $clientIds = $clients->pluck('id');

        return VisitReport::query()
            ->whereHas('careOrderDetails', function ($query) use ($clientIds) {
                $query->whereIn('client_profile_id', $clientIds);
            })
            ->with([
                'careOrderDetails.order',
                'careOrderDetails.careService',
                'helperProfile.user',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getRecentReportsForClient(ClientProfile $client, int $limit = 10): Collection
    {
        if (! $this->hasTables(['visit_reports', 'care_order_details'])) {
            return collect();
        }

        return VisitReport::query()
            ->whereHas('careOrderDetails', function ($query) use ($client) {
                $query->where('client_profile_id', $client->id);
            })
            ->with([
                'careOrderDetails.order',
                'careOrderDetails.careService',
                'helperProfile.user',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Проверка доступа пользователя к конкретному social care заказу.
     */
    public function userCanAccessCareOrder(User $user, Order $order): bool
    {
        if (! $this->hasTables(['trusted_contacts'])) {
            return false;
        }

        if ($order->service_type !== ServiceType::SOCIAL_CARE_VISIT->value) {
            return false;
        }

        $order->loadMissing('careDetails.clientProfile', 'careDetails.trustedContact');

        $clientProfile = $order->careDetails?->clientProfile;
        $trustedContact = $order->careDetails?->trustedContact;

        if (! $clientProfile) {
            return false;
        }

        if ($clientProfile->user_id === $user->id) {
            return true;
        }

        if ($trustedContact && $trustedContact->user_id === $user->id) {
            return true;
        }

        return TrustedContact::where('user_id', $user->id)
            ->where('client_profile_id', $clientProfile->id)
            ->exists();
    }

    protected function activeStatuses(): array
    {
        return collect(CareOrderStatus::cases())
            ->reject(fn (CareOrderStatus $status) => $status->isFinal())
            ->pluck('value')
            ->all();
    }
}
