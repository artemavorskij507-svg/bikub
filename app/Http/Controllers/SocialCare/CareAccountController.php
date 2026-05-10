<?php

namespace App\Http\Controllers\SocialCare;

use App\Events\SocialCare\CareOrderRescheduleRequested;
use App\Http\Controllers\Controller;
use App\Models\CareOrderChangeRequest;
use App\Models\CareOrderDetails;
use App\Models\CarePlan;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\TrustedContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareAccountController extends Controller
{
    private const FINAL_CARE_STATUSES = [
        'COMPLETED',
        'CANCELLED',
        'CANCELLED_BY_CLIENT',
        'CANCELLED_BY_OPERATOR',
        'CANCELLED_BY_TRUSTED_CONTACT',
        'NO_SHOW',
    ];

    private const ACTIVE_PLAN_STATUSES = ['ACTIVE', 'PAUSED'];

    private const MIN_CANCEL_HOURS = 2;

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $clientContexts = $this->gatherClientContexts($user);

        $clientSummaries = collect();

        if ($clientContexts->isNotEmpty()) {
            $clientIds = $clientContexts->pluck('client.id')->filter()->unique()->values();

            $planCounts = CarePlan::query()
                ->select('client_profile_id', DB::raw('count(*) as aggregate'))
                ->whereIn('client_profile_id', $clientIds)
                ->whereIn('status', self::ACTIVE_PLAN_STATUSES)
                ->groupBy('client_profile_id')
                ->pluck('aggregate', 'client_profile_id');

            $upcomingVisits = $this->careOrdersQuery($clientIds)
                ->whereNotIn('care_status', self::FINAL_CARE_STATUSES)
                ->where(function ($query) {
                    $query->whereNull('scheduled_start_at')
                        ->orWhere('scheduled_start_at', '>=', now()->subDay());
                })
                ->orderBy('scheduled_start_at')
                ->get()
                ->groupBy('client_profile_id');

            $recentVisits = $this->careOrdersQuery($clientIds)
                ->whereIn('care_status', self::FINAL_CARE_STATUSES)
                ->orderByDesc('scheduled_start_at')
                ->get()
                ->groupBy('client_profile_id');

            $clientSummaries = $clientContexts->map(function (array $context) use ($planCounts, $upcomingVisits, $recentVisits) {
                $client = $context['client'];
                $clientId = $client->id;

                return [
                    'client' => $client,
                    'relationship' => $context['relationship'],
                    'trusted_contact' => $context['trusted_contact'],
                    'active_plan_count' => $planCounts[$clientId] ?? 0,
                    'upcoming_visits' => ($upcomingVisits[$clientId] ?? collect())->take(3),
                    'recent_visits' => ($recentVisits[$clientId] ?? collect())->take(3),
                ];
            });
        }

        return view('care.dashboard', [
            'clients' => $clientSummaries,
        ]);
    }

    public function showClient(Request $request, ClientProfile $clientProfile): View
    {
        $user = $request->user();

        if (! $this->userCanAccessClient($user, $clientProfile)) {
            abort(403);
        }

        $clientProfile->load([
            'trustedContacts',
            'carePlans.careService',
        ]);

        $careOrders = $this->careOrdersQuery(collect([$clientProfile->id]), ['assignedHelper.user', 'visitReports'])
            ->orderBy('scheduled_start_at')
            ->get();

        $activePlans = $clientProfile->carePlans
            ->filter(fn ($plan) => in_array($plan->status, self::ACTIVE_PLAN_STATUSES, true))
            ->values();

        $upcomingVisits = $careOrders
            ->filter(fn ($visit) => ! $this->isFinalCareStatus($visit->care_status) && (
                ! $visit->scheduled_start_at || $visit->scheduled_start_at->isFuture()
            ))
            ->values();

        $pastVisits = $careOrders
            ->filter(fn ($visit) => $this->isFinalCareStatus($visit->care_status))
            ->sortByDesc('scheduled_start_at')
            ->take(20)
            ->values();

        $canManage = $this->canUserManageClient($user, $clientProfile);

        return view('care.client-show', [
            'client' => $clientProfile,
            'activePlans' => $activePlans,
            'upcomingVisits' => $upcomingVisits,
            'pastVisits' => $pastVisits,
            'canManage' => $canManage,
        ]);
    }

    public function showOrder(Request $request, Order $order): View
    {
        $this->ensureSocialCareOrder($order);

        $user = $request->user();

        if (! $this->userCanAccessOrder($user, $order)) {
            abort(403);
        }

        $order->load([
            'careDetails.clientProfile',
            'careDetails.trustedContact',
            'careDetails.careService',
            'careDetails.assignedHelper',
            'careDetails.visitReports.helperProfile',
        ]);

        $careDetails = $order->careDetails;

        if (! $careDetails) {
            abort(404);
        }

        $visitReport = $careDetails->visitReports->sortByDesc('created_at')->first();

        $canManage = $this->canUserManageOrder($user, $order);
        $canCancel = $canManage && $this->canCancelVisit($careDetails);
        $canRequestReschedule = $canManage && $this->canRequestReschedule($careDetails);

        return view('care.order-show', [
            'order' => $order,
            'careDetails' => $careDetails,
            'visitReport' => $visitReport,
            'canManage' => $canManage,
            'canCancel' => $canCancel,
            'canRequestReschedule' => $canRequestReschedule,
            'changeRequests' => $order->careChangeRequests()->latest()->get(),
        ]);
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureSocialCareOrder($order);

        $user = $request->user();

        if (! $this->canUserManageOrder($user, $order)) {
            abort(403);
        }

        $careDetails = $order->careDetails;

        if (! $careDetails) {
            abort(404);
        }

        if (! $this->canCancelVisit($careDetails)) {
            throw ValidationException::withMessages([
                'care_status' => __('Визит уже нельзя отменить онлайн. Пожалуйста, свяжитесь с координатором.'),
            ]);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $careDetails->clientProfile?->user_id === $user->id
            ? 'CANCELLED_BY_CLIENT'
            : 'CANCELLED_BY_TRUSTED_CONTACT';

        $careDetails->update([
            'care_status' => $newStatus,
        ]);

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['reason'] ?? 'Cancelled via care portal',
        ]);

        // TODO: dispatch domain event for cancellation (notify coordinators)

        return redirect()
            ->route('care.orders.show', $order)
            ->with('status', __('Визит отменён. Координатор получил уведомление.'));
    }

    public function requestReschedule(Request $request, Order $order): RedirectResponse
    {
        $this->ensureSocialCareOrder($order);

        $user = $request->user();

        if (! $this->canUserManageOrder($user, $order)) {
            abort(403);
        }

        $careDetails = $order->careDetails;

        if (! $careDetails) {
            abort(404);
        }

        if (! $this->canRequestReschedule($careDetails)) {
            throw ValidationException::withMessages([
                'new_date' => __('Перенос доступен только для будущих визитов.'),
            ]);
        }

        $data = $request->validate([
            'new_date' => ['required', 'date'],
            'new_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $requestedStart = Carbon::parse("{$data['new_date']} {$data['new_time']}", config('app.timezone'));
        } catch (\Exception $e) {
            \Log::warning('Failed to parse requested start time in CareAccountController', [
                'new_date' => $data['new_date'] ?? null,
                'new_time' => $data['new_time'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'new_date' => __('Неверный формат даты или времени.'),
            ]);
        }
        if ($requestedStart->lessThanOrEqualTo(now()->addHour())) {
            throw ValidationException::withMessages([
                'new_date' => __('Выберите время не ранее чем через час.'),
            ]);
        }

        $requestedEnd = null;
        if ($careDetails->scheduled_end_at && $careDetails->scheduled_start_at) {
            $durationMinutes = $careDetails->scheduled_start_at->diffInMinutes($careDetails->scheduled_end_at);
            $requestedEnd = (clone $requestedStart)->addMinutes($durationMinutes);
        }

        $changeRequest = CareOrderChangeRequest::create([
            'order_id' => $order->id,
            'requested_by_user_id' => $user->id,
            'requested_new_start_at' => $requestedStart,
            'requested_new_end_at' => $requestedEnd,
            'reason' => $data['reason'] ?? null,
            'status' => 'PENDING',
            'metadata' => [
                'previous_start_at' => $careDetails->scheduled_start_at,
                'previous_end_at' => $careDetails->scheduled_end_at,
            ],
        ]);

        // Dispatch event
        event(new CareOrderRescheduleRequested($order, $changeRequest));

        return redirect()
            ->route('care.orders.show', $order)
            ->with('status', __('Запрос на перенос отправлен координатору.'));
    }

    protected function userCanAccessClient(User $user, ClientProfile $client): bool
    {
        if ($client->user_id === $user->id) {
            return true;
        }

        return TrustedContact::query()
            ->where('client_profile_id', $client->id)
            ->where('user_id', $user->id)
            ->where('can_view_reports', true)
            ->exists();
    }

    protected function canUserManageClient(User $user, ClientProfile $client): bool
    {
        if ($client->user_id === $user->id) {
            return true;
        }

        $contact = $this->findTrustedContactFor($user, $client);

        return (bool) $contact?->can_manage_orders;
    }

    protected function userCanAccessOrder(User $user, Order $order): bool
    {
        $careDetails = $order->careDetails;

        if (! $careDetails) {
            return false;
        }

        return $this->userCanAccessClient($user, $careDetails->clientProfile);
    }

    protected function canUserManageOrder(User $user, Order $order): bool
    {
        $careDetails = $order->careDetails;

        if (! $careDetails) {
            return false;
        }

        return $this->canUserManageClient($user, $careDetails->clientProfile);
    }

    protected function canCancelVisit(CareOrderDetails $details): bool
    {
        if ($this->isFinalCareStatus($details->care_status)) {
            return false;
        }

        if (! $details->scheduled_start_at) {
            return true;
        }

        return $details->scheduled_start_at->greaterThan(now()->addHours(self::MIN_CANCEL_HOURS));
    }

    protected function canRequestReschedule(CareOrderDetails $details): bool
    {
        if ($this->isFinalCareStatus($details->care_status)) {
            return false;
        }

        if (! $details->scheduled_start_at) {
            return true;
        }

        return $details->scheduled_start_at->isFuture();
    }

    protected function gatherClientContexts(User $user): Collection
    {
        $contexts = collect();

        $ownProfile = ClientProfile::query()
            ->where('user_id', $user->id)
            ->first();

        if ($ownProfile) {
            $contexts->push([
                'client' => $ownProfile,
                'relationship' => 'self',
                'trusted_contact' => null,
            ]);
        }

        $trustedContacts = TrustedContact::query()
            ->with('clientProfile')
            ->where('user_id', $user->id)
            ->get();

        foreach ($trustedContacts as $contact) {
            if (! $contact->clientProfile) {
                continue;
            }

            $contexts->push([
                'client' => $contact->clientProfile,
                'relationship' => 'trusted',
                'trusted_contact' => $contact,
            ]);
        }

        return $contexts->unique(fn ($context) => $context['client']->id)->values();
    }

    protected function careOrdersQuery(Collection $clientIds, array $with = []): \Illuminate\Database\Eloquent\Builder
    {
        $relations = array_unique(array_merge([
            'careService',
            'order',
            'assignedHelper',
        ], $with));

        return CareOrderDetails::query()
            ->with($relations)
            ->whereIn('client_profile_id', $clientIds);
    }

    protected function findTrustedContactFor(User $user, ClientProfile $client): ?TrustedContact
    {
        return TrustedContact::query()
            ->where('client_profile_id', $client->id)
            ->where('user_id', $user->id)
            ->first();
    }

    protected function ensureSocialCareOrder(Order $order): void
    {
        if (method_exists($order, 'isSocialCare') && ! $order->isSocialCare()) {
            abort(404);
        }
    }

    protected function isFinalCareStatus(?string $status): bool
    {
        if (! $status) {
            return false;
        }

        return in_array($status, self::FINAL_CARE_STATUSES, true)
            || str_starts_with($status, 'CANCELLED');
    }
}
