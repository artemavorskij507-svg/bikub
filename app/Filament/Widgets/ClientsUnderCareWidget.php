<?php

namespace App\Filament\Widgets;

use App\Models\CareOrderDetails;
use App\Models\CarePlan;
use App\Models\ClientProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientsUnderCareWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        // Clients with visits in the next week
        $clientsWithVisits = CareOrderDetails::query()
            ->whereBetween('scheduled_start_at', [$weekStart, $weekEnd])
            ->whereNotNull('client_profile_id')
            ->distinct('client_profile_id')
            ->count('client_profile_id');

        // Clients with active care plans
        $clientsWithPlans = CarePlan::query()
            ->where('status', 'ACTIVE')
            ->whereNotNull('client_profile_id')
            ->distinct('client_profile_id')
            ->count('client_profile_id');

        // Total active clients
        $totalActive = ClientProfile::query()
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Активных клиентов', $totalActive)
                ->description('Всего в системе')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('С визитами на неделю', $clientsWithVisits)
                ->description('Запланированы визиты')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('С активными планами', $clientsWithPlans)
                ->description('Регулярный уход')
                ->descriptionIcon('heroicon-o-heart')
                ->color('success'),
        ];
    }
}
