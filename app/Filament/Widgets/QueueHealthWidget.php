<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $failedJobsCount = DB::table('failed_jobs')->count();

        // Get queue metrics (approximate for jobs table)
        $waitingJobs = DB::table('jobs')->count();

        // Calculate average wait time (simplified - in production use actual queue metrics)
        $avgWaitTime = 0;
        $oldestJob = DB::table('jobs')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($oldestJob) {
            try {
                $createdAt = $oldestJob->created_at instanceof \Carbon\Carbon
                    ? $oldestJob->created_at
                    : \Carbon\Carbon::parse($oldestJob->created_at);
                $avgWaitTime = now()->diffInSeconds($createdAt);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse created_at in QueueHealthWidget', [
                    'job_id' => $oldestJob->id ?? null,
                    'created_at' => $oldestJob->created_at ?? null,
                    'error' => $e->getMessage(),
                ]);
                $avgWaitTime = 0;
            }
        }

        $waitMinutes = round($avgWaitTime / 60, 1);

        return [
            Stat::make('Ожидающие задачи', $waitingJobs)
                ->description('В очереди')
                ->descriptionIcon('heroicon-o-clock')
                ->color($waitingJobs > 100 ? 'danger' : ($waitingJobs > 50 ? 'warning' : 'success')),

            Stat::make('Неудачные задачи', $failedJobsCount)
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-o-exclamation')
                ->color($failedJobsCount > 5 ? 'danger' : ($failedJobsCount > 0 ? 'warning' : 'success')),

            Stat::make('Среднее время ожидания', $waitMinutes > 0 ? "{$waitMinutes} мин" : 'Нет')
                ->description('Самый старый в очереди')
                ->descriptionIcon('heroicon-o-clock')
                ->color($waitMinutes > 10 ? 'danger' : ($waitMinutes > 5 ? 'warning' : 'success')),
        ];
    }
}
