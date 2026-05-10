<?php

namespace App\Console\Commands;

use App\Models\Operations\ServiceJob;
use App\Services\Operations\OperationsSlaService;
use Illuminate\Console\Command;

class OperationsEvaluateSla extends Command
{
    protected $signature = 'operations:evaluate-sla {--organization=}';

    protected $description = 'Evaluate SLA timers and raise warnings/breaches for active service jobs';

    public function handle(OperationsSlaService $slaService): int
    {
        $organizationId = $this->option('organization');

        $query = ServiceJob::query()
            ->whereIn('status', ['pending', 'ready_for_dispatch', 'assigned', 'accepted', 'arrived', 'started']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $count = 0;
        $query->chunkById(200, function ($jobs) use ($slaService, &$count) {
            foreach ($jobs as $job) {
                $slaService->evaluate($job);
                $count++;
            }
        });

        $this->info("SLA evaluated for {$count} jobs.");

        return self::SUCCESS;
    }
}

