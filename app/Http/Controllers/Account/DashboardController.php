<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Presenters\OrderPresenter;
use App\Services\Account\AccountContextManager;
use App\Services\Account\AccountReadService;
use App\Services\Account\TimelineService;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        AccountReadService $accountRead,
        CareAccountReadService $careRead,
        AccountContextManager $contextManager,
        TimelineService $timelineService
    ): View {
        $user = auth()->user();

        // Проверка роли курьера или исполнителя
        if ($user->hasRole('courier') || $user->hasRole('executor')) {
            return $this->courierDashboard($user);
        }

        // Обычный дашборд для клиентов
        $activeClient = $contextManager->getActiveClient($user);

        $orderCards = $accountRead
            ->getRecentOrdersForUserAndClient($user, $activeClient, 5)
            ->map([OrderPresenter::class, 'forAccount']);

        $upcomingCareVisits = $activeClient
            ? $careRead->getUpcomingVisitsForClient($activeClient, 3)
            : $careRead->getUpcomingVisitsForUser($user, 3);

        $hasSocialCareAccess = $careRead->userHasAnyCareRelation($user);
        $kpi = $accountRead->getOrderKpiForUser($user);
        $timeline = $timelineService->getTimelineForUser($user, 30);

        return view('account.dashboard', [
            'orderCards' => $orderCards,
            'upcomingCareVisits' => $upcomingCareVisits,
            'hasSocialCareAccess' => $hasSocialCareAccess,
            'kpi' => $kpi,
            'activeClient' => $activeClient,
            'timeline' => $timeline,
        ]);
    }

    protected function courierDashboard($user): View
    {
        // Активный заказ (назначенный на курьера)
        $activeOrder = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'en_route'])
            ->with(['deliveryOrder', 'address', 'user'])
            ->first();

        // Статистика за сегодня
        $todayStart = now()->startOfDay();
        $todayEarnings = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $todayStart)
            ->sum('total_amount') ?? 0;

        $todayCompleted = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $todayStart)
            ->count();

        // Статус смены (можно хранить в сессии или в БД)
        $isOnline = session('courier_online', false);

        // Статистика за месяц
        $monthStart = now()->startOfMonth();
        $monthCompleted = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $monthStart)
            ->count();

        return view('account.courier-dashboard', [
            'user' => $user,
            'activeOrder' => $activeOrder,
            'todayEarnings' => $todayEarnings,
            'todayCompleted' => $todayCompleted,
            'monthCompleted' => $monthCompleted,
            'isOnline' => $isOnline,
        ]);
    }
}
