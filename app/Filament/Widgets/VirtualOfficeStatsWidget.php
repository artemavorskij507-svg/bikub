<?php

namespace App\Filament\Widgets;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Message;
use App\Models\VirtualOffice\OfficeZone;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VirtualOfficeStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Agents', Agent::count())
                ->description('All virtual office agents')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Active Agents', Agent::where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([3, 5, 7, 4, 6, 5, 4]),

            Stat::make('Inactive Agents', Agent::where('is_active', false)->count())
                ->description('Currently inactive')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('warning')
                ->chart([5, 4, 3, 6, 4, 5, 6]),

            Stat::make('Total Tasks', Task::count())
                ->description('All tasks created')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->chart([4, 6, 3, 5, 7, 4, 5]),

            Stat::make('Pending Tasks', Task::where('status', 'pending')->count())
                ->description('Awaiting assignment')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->chart([6, 4, 5, 3, 4, 6, 5]),

            Stat::make('Completed Tasks', Task::where('status', 'completed')->count())
                ->description('Successfully finished')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([3, 5, 4, 6, 5, 4, 7]),

            Stat::make('Office Zones', OfficeZone::count())
                ->description('Active workspaces')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->chart([6, 6, 6, 6, 6, 6, 6]),

            Stat::make('Messages', Message::count())
                ->description('Total communications')
                ->descriptionIcon('heroicon-o-chat-alt')
                ->color('info')
                ->chart([5, 4, 6, 3, 5, 7, 4]),
        ];
    }
}
