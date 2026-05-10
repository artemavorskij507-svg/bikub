<?php

namespace App\Filament\Resources\SupportTicketResource\Widgets;

use App\Models\SupportTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SupportTicketsOverviewWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $this->seedLocalSupportTicketsIfEmpty();

        $openCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $resolvedToday = SupportTicket::whereIn('status', ['resolved', 'closed'])
            ->whereDate('resolved_at', Carbon::today())
            ->count();
        $urgentCount = SupportTicket::where('priority', 'urgent')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
        $avgResolution = SupportTicket::whereNotNull('resolved_at')
            ->get()
            ->avg(fn (SupportTicket $ticket) => $ticket->resolved_at?->diffInMinutes($ticket->created_at) ?? 0) ?? 0;

        return [
            Card::make('Open Tickets', $openCount)
                ->description('Waiting in support queue')
                ->descriptionIcon('heroicon-s-inbox')
                ->color('warning'),
            Card::make('Resolved Today', $resolvedToday)
                ->description('Closed in the last 24h')
                ->descriptionIcon('heroicon-s-check-circle')
                ->color('success'),
            Card::make('Urgent Tickets', $urgentCount)
                ->description('Priority: urgent')
                ->descriptionIcon('heroicon-s-flag')
                ->color('danger'),
            Card::make('Avg Resolution Time', round($avgResolution).' min')
                ->description('Calculated from resolved tickets')
                ->descriptionIcon('heroicon-s-clock')
                ->color('info'),
        ];
    }

    protected function seedLocalSupportTicketsIfEmpty(): void
    {
        $host = request()->getHost();
        $isLocalRuntime = in_array($host, ['127.0.0.1', 'localhost'], true) || (bool) config('app.debug');

        if (! $isLocalRuntime || ! Schema::hasTable('support_tickets')) {
            return;
        }

        if (DB::table('support_tickets')->exists()) {
            $this->normalizeDemoRows();
            return;
        }

        try {
            $columns = collect(DB::select("PRAGMA table_info('support_tickets')"));
            if ($columns->isEmpty()) {
                return;
            }

            $columnNames = $columns->pluck('name')->all();
            $userId = auth()->id();
            if (! $userId && Schema::hasTable('users')) {
                $userId = DB::table('users')->orderBy('id')->value('id');
            }

            $rows = [
                [
                    'id' => (string) Str::uuid(),
                    'number' => 'TKT-'.date('Y').'-9101',
                    'subject' => 'Demo: payment issue',
                    'message' => 'Local seeded ticket for support dashboard',
                    'description' => 'Local seeded ticket for support dashboard',
                    'status' => 'open',
                    'priority' => 'high',
                    'role_context' => 'client',
                    'channel' => 'worker_lk',
                    'source' => 'web_form',
                    'user_id' => $userId,
                    'created_at' => now()->subHours(2),
                    'updated_at' => now()->subHours(2),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'number' => 'TKT-'.date('Y').'-9102',
                    'subject' => 'Demo: task visibility',
                    'message' => 'Local seeded in-progress ticket',
                    'description' => 'Local seeded in-progress ticket',
                    'status' => 'in_progress',
                    'priority' => 'urgent',
                    'role_context' => 'worker',
                    'channel' => 'worker_lk',
                    'source' => 'web_form',
                    'user_id' => $userId,
                    'created_at' => now()->subHour(),
                    'updated_at' => now()->subMinutes(30),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'number' => 'TKT-'.date('Y').'-9103',
                    'subject' => 'Demo: resolved inquiry',
                    'message' => 'Local seeded resolved ticket',
                    'description' => 'Local seeded resolved ticket',
                    'status' => 'resolved',
                    'priority' => 'normal',
                    'role_context' => 'client',
                    'channel' => 'worker_lk',
                    'source' => 'web_form',
                    'user_id' => $userId,
                    'resolved_at' => now()->subMinutes(10),
                    'resolved_by' => $userId,
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subMinutes(10),
                ],
            ];

            $idCol = $columns->firstWhere('name', 'id');
            $idType = strtolower((string) ($idCol->type ?? ''));

            foreach ($rows as $row) {
                $prepared = [];

                foreach ($columnNames as $name) {
                    if ($name === 'id' && str_contains($idType, 'int')) {
                        continue;
                    }

                    if (array_key_exists($name, $row)) {
                        $prepared[$name] = $row[$name];
                    }
                }

                if (in_array('description', $columnNames, true) && ! isset($prepared['description'])) {
                    $prepared['description'] = $row['message'] ?? 'Support demo ticket';
                }

                if (in_array('source', $columnNames, true) && ! isset($prepared['source'])) {
                    $prepared['source'] = 'web_form';
                }

                if (in_array('channel', $columnNames, true) && ! isset($prepared['channel'])) {
                    $prepared['channel'] = 'worker_lk';
                }

                if (in_array('status', $columnNames, true) && ! isset($prepared['status'])) {
                    $prepared['status'] = 'open';
                }

                if (in_array('priority', $columnNames, true) && ! isset($prepared['priority'])) {
                    $prepared['priority'] = 'normal';
                }

                DB::table('support_tickets')->insert($prepared);
            }
        } catch (\Throwable $e) {
            Log::warning('SupportTicketsOverviewWidget seed failed', ['error' => $e->getMessage()]);
        }
    }

    protected function normalizeDemoRows(): void
    {
        try {
            $columns = collect(DB::select("PRAGMA table_info('support_tickets')"))->pluck('name')->all();
            if (empty($columns)) {
                return;
            }

            $updates = [
                'subject' => 'Demo support ticket',
                'message' => 'Normalized local demo text',
                'description' => 'Normalized local demo text',
                'source' => 'web_form',
                'channel' => 'worker_lk',
                'updated_at' => now(),
            ];
            $updates = array_intersect_key($updates, array_flip($columns));

            if (empty($updates)) {
                return;
            }

            DB::table('support_tickets')
                ->where('number', 'like', 'TKT-%-91%')
                ->update($updates);
        } catch (\Throwable $e) {
            Log::warning('SupportTicketsOverviewWidget normalize failed', ['error' => $e->getMessage()]);
        }
    }
}
