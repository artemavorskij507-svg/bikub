<?php

namespace App\Console\Commands;

use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use Illuminate\Console\Command;

class PruneWorkbenchIdempotencyKeysCommand extends Command
{
    protected $signature = 'ops:prune-workbench-idempotency {--days=7} {--processing-minutes=30}';

    protected $description = 'Prune old/stale workbench idempotency keys';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $processingMinutes = max(5, (int) $this->option('processing-minutes'));

        $prunedTerminal = WorkbenchIdempotencyKey::query()
            ->whereIn('state', ['completed', 'failed'])
            ->where('updated_at', '<=', now()->subDays($days))
            ->delete();

        $prunedStaleProcessing = WorkbenchIdempotencyKey::query()
            ->where('state', 'processing')
            ->where(function ($q) use ($processingMinutes): void {
                $q->where('started_at', '<=', now()->subMinutes($processingMinutes))
                    ->orWhere('created_at', '<=', now()->subMinutes($processingMinutes));
            })
            ->delete();

        $this->info("Pruned terminal keys: {$prunedTerminal}");
        $this->info("Pruned stale processing keys: {$prunedStaleProcessing}");

        return self::SUCCESS;
    }
}

