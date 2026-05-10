<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vegvesen data refresh - hourly
        // Run incidents at :00 every hour
        $schedule->command('vegvesen:ingest-incidents')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Vegvesen incidents ingestion failed');
            });

        // Run travel times at :05 every hour (5 minutes after incidents)
        $schedule->command('vegvesen:ingest-travel-times')
            ->hourlyAt(5)
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Vegvesen travel times ingestion failed');
            });
        // Expire holds every minute
        $schedule->command('slots:expire-holds')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Analytics incremental
        $schedule->command('analytics:refresh-incremental')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('pricing:demand:refresh')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Nightly rebuild snapshots
        $schedule->command('analytics:rebuild-snapshots --from=yesterday --to=today')
            ->dailyAt('02:10')
            ->withoutOverlapping();

        // Roadside SLA checks - every 5 minutes
        $schedule->command('roadside:check-sla')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Roadside SLA check failed');
            });

        // Claim SLA checks - every 5 minutes
        $schedule->command('claims:check-sla')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Claim SLA check failed');
            });

        // Assistant broadcast - every 5 minutes
        $schedule->command('assistant:broadcast')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Classifieds: ежедневная проверка срока действия объявлений
        $schedule->command('classifieds:check-expiration')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Classifieds: периодическая обработка сохранённых поисков (алерты)
        $schedule->command('classifieds:process-alerts')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Classifieds: синхронизация счетчиков просмотров из Redis в БД
        $schedule->command('classifieds:sync-views')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Unified Operations Core SLA checks
        $schedule->command('operations:evaluate-sla')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Operations SLA evaluation failed');
            });

        $schedule->job(new \App\Domain\Sla\Jobs\EvaluateSlaTimersJob)
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new \App\Domain\Tracking\Jobs\DetectStaleExecutorPingsJob(5))
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new \App\Domain\Tracking\Jobs\DetectStalledAssignmentsJob(10, 15))
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new \App\Domain\AgentOS\Jobs\DetectStaleAgentStepsJob(
            (int) config('agent-os.timeout.heartbeat_grace_minutes', 2)
        ))
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new \App\Domain\AgentOS\Jobs\CompactAgentMemoriesJob)
            ->dailyAt('03:20')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('ops:prune-workbench-idempotency')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('ops:payment-readiness --insecure --json=storage/app/ops-payment-readiness-report.json')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Ops payment readiness command failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
