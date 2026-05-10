<?php

namespace App\Support\Lk;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class Navigation
{
    public static function forUser(User $user): array
    {
        $hasRole = fn (array $roles): bool => method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole($roles)
            : true;

        $activeOrdersCount = 0;
        if ($hasRole(['courier', 'executor', 'eco_executor', 'roadside_assist', 'admin', 'operator'])) {
            $activeOrdersCount = cache()->remember(
                "user_{$user->id}_active_orders_count",
                now()->addMinutes(5),
                fn () => Order::where('assigned_to', $user->id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count()
            );
        }

        $unreadNotificationsCount = 0;
        if (Schema::hasTable('notifications')) {
            $unreadNotificationsCount = cache()->remember(
                "user_{$user->id}_unread_notifications_count",
                now()->addMinutes(2),
                fn () => $user->unreadNotifications()->count()
            );
        }

        $roadsideJobsCount = 0;
        if ($hasRole(['roadside_assist', 'eco_executor', 'executor', 'tow_operator'])) {
            $roadsideJobsCount = cache()->remember(
                "user_{$user->id}_roadside_jobs_active_count",
                now()->addMinutes(5),
                function () use ($user) {
                    return \App\Models\RoadsideEmergency::query()
                        ->where(function ($q) use ($user) {
                            $q->whereHas('order', function ($oq) use ($user) {
                                $oq->where('assigned_to', $user->id);
                            })->orWhereHas('helper', function ($hq) use ($user) {
                                $hq->where('user_id', $user->id);
                            });
                        })
                        ->active()
                        ->count();
                }
            );
        }

        return [
            [
                'key' => 'dashboard',
                'label' => 'Главная',
                'icon' => 'heroicon-o-home',
                'route' => 'lk.dashboard',
                'visible' => true,
            ],
            [
                'key' => 'orders',
                'label' => 'Мои заказы',
                'icon' => 'heroicon-o-briefcase',
                'route' => 'lk.orders.index',
                'visible' => $hasRole(['courier', 'executor', 'eco_executor', 'roadside_assist', 'admin', 'operator', 'handyman']),
                'badge' => $activeOrdersCount > 0 ? $activeOrdersCount : null,
            ],
            [
                'key' => 'executor_jobs',
                'label' => 'Задания мастера',
                'icon' => 'heroicon-o-briefcase',
                'route' => 'lk.executor.jobs.index',
                'visible' => $hasRole(['executor', 'handyman']),
            ],
            [
                'key' => 'roadside_jobs',
                'label' => 'Дорожные задания',
                'icon' => 'heroicon-o-truck',
                'route' => 'lk.roadside-jobs.index',
                'visible' => $hasRole(['roadside_assist', 'eco_executor', 'executor', 'tow_operator']),
                'badge' => $roadsideJobsCount > 0 ? $roadsideJobsCount : null,
            ],
            [
                'key' => 'schedule',
                'label' => 'График',
                'icon' => 'heroicon-o-calendar',
                'route' => 'lk.schedule',
                'visible' => $hasRole(['courier', 'executor', 'social_helper', 'eco_executor', 'roadside_assist', 'admin', 'operator']),
            ],
            [
                'key' => 'wallet',
                'label' => 'Кошелек',
                'icon' => 'heroicon-o-wallet',
                'route' => 'lk.wallet',
                'visible' => $hasRole(['courier', 'executor', 'eco_executor', 'roadside_assist', 'social_helper', 'admin', 'operator']),
            ],
            [
                'key' => 'notifications',
                'label' => 'Уведомления',
                'icon' => 'heroicon-o-bell',
                'route' => 'lk.notifications',
                'visible' => true,
                'badge' => $unreadNotificationsCount > 0 ? $unreadNotificationsCount : null,
            ],
            [
                'key' => 'support',
                'label' => 'Поддержка',
                'icon' => 'heroicon-o-life-buoy',
                'route' => 'lk.support',
                'visible' => true,
            ],
            [
                'key' => 'settings',
                'label' => 'Настройки',
                'icon' => 'heroicon-o-adjustments',
                'route' => 'lk.settings',
                'visible' => true,
            ],
        ];
    }
}
