<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

// TODO fixed by Cursor: internal LK debug controller, safe to ignore for production flows
class DebugController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Активные заказы
        $activeOrders = Order::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Завершённые заказы
        $completedOrders = Order::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Выплаты
        $payouts = Payout::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Тикеты
        $tickets = SupportTicket::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Онлайн-статус
        $workerStatus = $user->workerStatus ?? null;

        // Уведомления (если таблица есть)
        $unreadNotificationsCount = 0;
        if (Schema::hasTable('notifications') && method_exists($user, 'unreadNotifications')) {
            $unreadNotificationsCount = $user->unreadNotifications()->count();
        }

        // Смены (через employee → scheduleSlots, если есть)
        $employee = $user->employee ?? null;
        $upcomingShifts = collect();

        if ($employee && method_exists($employee, 'scheduleSlots')) {
            $upcomingShifts = $employee->scheduleSlots()
                ->where('start_at', '>=', now()->startOfDay())
                ->with('zone')
                ->orderBy('start_at')
                ->limit(10)
                ->get();
        }

        return view('lk.debug', [
            'user' => $user,
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'payouts' => $payouts,
            'tickets' => $tickets,
            'workerStatus' => $workerStatus,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'upcomingShifts' => $upcomingShifts,
        ]);
    }
}
