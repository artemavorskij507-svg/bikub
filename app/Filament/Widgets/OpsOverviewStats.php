<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SupportTicket;
use App\Models\WorkerApplication;
use App\Models\WorkerStatus;
use Illuminate\Support\Facades\Schema;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class OpsOverviewStats extends BaseWidget
{
    protected function getCards(): array
    {
        $today = now()->startOfDay();
        $ordersTable = (new Order())->getTable();
        $payoutsTable = (new Payout())->getTable();
        $supportTicketsTable = (new SupportTicket())->getTable();
        $workerStatusesTable = class_exists(WorkerStatus::class) ? (new WorkerStatus())->getTable() : null;
        $workerApplicationsTable = class_exists(WorkerApplication::class) ? (new WorkerApplication())->getTable() : null;

        $safeOrderCount = fn ($query) => Schema::hasTable($ordersTable) ? (string) $query->count() : '0';
        $revenueToday = Schema::hasTable($ordersTable)
            ? Order::whereIn('payment_status', ['captured', 'paid'])->where('updated_at', '>=', $today)->sum('total_amount')
            : 0;

        return [
            Card::make('Today Orders', $safeOrderCount(Order::where('created_at', '>=', $today))),
            Card::make('Active Orders', $safeOrderCount(Order::whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'in_progress', 'arrived']))),
            Card::make('Waiting Dispatch', $safeOrderCount(Order::where('status', 'waiting_dispatch'))),
            Card::make('Completed Today', $safeOrderCount(Order::where('status', 'completed')->where('completed_at', '>=', $today))),
            Card::make('Revenue Today', number_format((float) $revenueToday, 2)),
            Card::make('Pending Payouts', Schema::hasTable($payoutsTable) ? (string) Payout::whereIn('status', ['pending', 'approved', 'processing'])->count() : '0'),
            Card::make('Active Workers', ($workerStatusesTable && Schema::hasTable($workerStatusesTable)) ? (string) WorkerStatus::where('is_online', true)->count() : '0'),
            Card::make('SLA Risk', $safeOrderCount(Order::where('sla_breach_risk', true))),
            Card::make('Disputed/Problem', $safeOrderCount(Order::whereIn('status', ['disputed', 'failed', 'cancelled']))),
            Card::make('New Worker Applications', ($workerApplicationsTable && Schema::hasTable($workerApplicationsTable)) ? (string) WorkerApplication::where('status', 'new_application')->count() : '0'),
            Card::make('Support Tickets', Schema::hasTable($supportTicketsTable) ? (string) SupportTicket::count() : '0'),
        ];
    }
}
