<?php

namespace App\Console\Commands;

use App\Models\Operations\SlaTimer;
use Illuminate\Console\Command;

class OpsSeedSlaDemoMixCommand extends Command
{
    protected $signature = 'ops:demo-sla-mix {--dry-run : Show distribution without writing}';

    protected $description = 'Create demo SLA mix (mostly OK, some warning, few breached)';

    public function handle(): int
    {
        $timers = SlaTimer::query()->orderBy('id')->get();

        if ($timers->isEmpty()) {
            $this->warn('No SLA timers found.');

            return self::SUCCESS;
        }

        $total = $timers->count();
        $warningCount = max(1, (int) floor($total * 0.2));
        $breachedCount = max(1, (int) floor($total * 0.1));
        $okCount = max(0, $total - $warningCount - $breachedCount);

        $this->info("Target mix => ok: {$okCount}, warning: {$warningCount}, breached: {$breachedCount}");

        if ($this->option('dry-run')) {
            $this->line('Dry run mode. No updates applied.');

            return self::SUCCESS;
        }

        $i = 0;
        foreach ($timers as $timer) {
            $state = 'pending';
            if ($i >= $okCount && $i < $okCount + $warningCount) {
                $state = 'warning';
            } elseif ($i >= $okCount + $warningCount) {
                $state = 'breached';
            }

            $timer->status = $state;
            if (in_array($state, ['warning', 'breached'], true)) {
                $timer->warning_at = $timer->warning_at ?: now()->subMinutes(10);
            }
            if ($state === 'breached') {
                $timer->breach_at = $timer->breach_at ?: now()->subMinutes(5);
            }

            if ($state === 'pending') {
                $timer->dispatch_state = 'ok';
                $timer->arrival_state = 'ok';
                $timer->completion_state = 'ok';
            }
            if ($state === 'warning') {
                $timer->dispatch_state = 'warning';
                $timer->arrival_state = 'ok';
                $timer->completion_state = 'ok';
            }
            if ($state === 'breached') {
                $timer->dispatch_state = 'breached';
                $timer->arrival_state = 'warning';
                $timer->completion_state = 'warning';
            }

            $timer->last_evaluated_at = now();
            $timer->save();
            $i++;
        }

        $this->info('Demo SLA mix applied.');

        return self::SUCCESS;
    }
}
