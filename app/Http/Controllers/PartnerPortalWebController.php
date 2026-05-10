<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerUser;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PartnerPortalWebController extends Controller
{
    private const ORDERS_PARTNER_FALLBACK_WARNING = 'Partner order binding column is not available in orders table.';

    public function dashboard(Request $request): View
    {
        [$partnerId, $isPrivileged, $profileWarning] = $this->resolvePartnerContext($request);
        $partnerColumn = $this->partnerOrderColumn();
        $partner = $partnerId ? Partner::query()->find($partnerId) : null;
        $orders = $partnerId && $partnerColumn
            ? $this->basePartnerOrdersQuery($partnerId)->limit(8)->get()
            : collect();
        $kpi = $partnerId && $partnerColumn
            ? $this->buildOrderKpi($partnerId)
            : $this->emptyOrderKpi();
        $summary = $partnerId
            ? $this->buildPartnerSummary($partnerId)
            : ['contracts_total' => 0, 'payouts_total' => 0, 'payouts_pending' => 0];
        $effectiveWarning = $profileWarning;
        if (! $partnerColumn) {
            $effectiveWarning = trim(($effectiveWarning ? $effectiveWarning.' ' : '').self::ORDERS_PARTNER_FALLBACK_WARNING);
        }

        return view('partner.dashboard', [
            'partner' => $partner,
            'orders' => $orders,
            'kpi' => $kpi,
            'summary' => $summary,
            'profileWarning' => $effectiveWarning,
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function orders(Request $request): View
    {
        [$partnerId, $isPrivileged, $profileWarning] = $this->resolvePartnerContext($request);
        $partnerColumn = $this->partnerOrderColumn();
        $orders = $partnerId && $partnerColumn
            ? $this->basePartnerOrdersQuery($partnerId)->paginate(20)
            : $this->emptyPaginator();
        $effectiveWarning = $profileWarning;
        if (! $partnerColumn) {
            $effectiveWarning = trim(($effectiveWarning ? $effectiveWarning.' ' : '').self::ORDERS_PARTNER_FALLBACK_WARNING);
        }

        return view('partner.orders', [
            'orders' => $orders,
            'profileWarning' => $effectiveWarning,
            'isPrivileged' => $isPrivileged,
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderLifecycleService $lifecycle): RedirectResponse
    {
        [$partnerId] = $this->resolvePartnerContext($request);
        $partnerColumn = $this->partnerOrderColumn();
        abort_unless($partnerId && $partnerColumn && (int) ($order->{$partnerColumn} ?? 0) === (int) $partnerId, 403);

        $data = $request->validate([
            'status' => ['required', 'in:accepted,preparing,ready,handed_to_courier,cancelled'],
        ]);

        $map = [
            'accepted' => 'worker_accepted',
            'preparing' => 'in_progress',
            'ready' => 'arrived',
            'handed_to_courier' => 'assigned',
            'cancelled' => 'cancelled',
        ];

        $lifecycle->transition(
            $order,
            $map[$data['status']],
            $request->user()->id,
            ['source' => 'partner_portal', 'partner_status' => $data['status']]
        );

        return back()->with('status', 'Order status updated');
    }

    private function resolvePartnerContext(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 403);

        $privilegedRoles = ['owner', 'admin', 'ops_admin', 'ops_manager', 'operator', 'dispatcher', 'support'];
        $workerRoles = ['partner'];

        $isPrivileged = method_exists($user, 'hasRole')
            ? collect($privilegedRoles)->contains(fn (string $role): bool => $user->hasRole($role))
            : false;
        $isPartnerUser = method_exists($user, 'hasRole')
            ? collect($workerRoles)->contains(fn (string $role): bool => $user->hasRole($role))
            : false;

        abort_unless($isPrivileged || $isPartnerUser, 403);
        $partnerUser = PartnerUser::where('user_id', $user->id)->first();
        if (! $partnerUser) {
            if ($isPrivileged) {
                return [null, true, 'No linked partner profile for this account. Link PartnerUser to inspect partner orders.'];
            }

            abort(403);
        }

        return [(int) $partnerUser->partner_id, $isPrivileged, null];
    }

    private function basePartnerOrdersQuery(int $partnerId): Builder
    {
        $partnerColumn = $this->partnerOrderColumn();
        abort_unless($partnerColumn !== null, 404);

        return Order::query()
            ->where($partnerColumn, $partnerId)
            ->with(['user', 'assignedUser', 'address'])
            ->latest('id');
    }

    private function buildOrderKpi(int $partnerId): array
    {
        $partnerColumn = $this->partnerOrderColumn();
        if (! $partnerColumn) {
            return $this->emptyOrderKpi();
        }
        $base = Order::query()->where($partnerColumn, $partnerId);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereIn('status', ['pending', 'confirmed', 'waiting_dispatch', 'assigned', 'worker_accepted', 'in_progress', 'arrived'])->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->whereIn('status', ['cancelled', 'disputed', 'failed'])->count(),
            'payment_issues' => (clone $base)->whereIn('payment_status', ['failed', 'refunded'])->count(),
        ];
    }

    private function partnerOrderColumn(): ?string
    {
        if (Schema::hasColumn('orders', 'partner_id')) {
            return 'partner_id';
        }

        if (Schema::hasColumn('orders', 'roadside_partner_id')) {
            return 'roadside_partner_id';
        }

        return null;
    }

    private function emptyOrderKpi(): array
    {
        return ['total' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0, 'payment_issues' => 0];
    }

    private function buildPartnerSummary(int $partnerId): array
    {
        $contractsTotal = 0;
        if (Schema::hasTable('partner_contracts')) {
            $contractsTotal = (int) \DB::table('partner_contracts')->where('partner_id', $partnerId)->count();
        }

        $payoutsTotal = 0;
        $payoutsPending = 0;
        if (Schema::hasTable('payouts')) {
            $payoutQuery = \DB::table('payouts')->where('partner_id', $partnerId);
            $payoutsTotal = (int) (clone $payoutQuery)->count();
            $payoutsPending = (int) (clone $payoutQuery)->whereIn('status', ['pending', 'processing', 'approved'])->count();
        }

        return [
            'contracts_total' => $contractsTotal,
            'payouts_total' => $payoutsTotal,
            'payouts_pending' => $payoutsPending,
        ];
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            collect(),
            0,
            20,
            \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }
}
