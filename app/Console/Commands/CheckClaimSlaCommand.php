<?php

namespace App\Console\Commands;

use App\Events\ClaimSlaBreached;
use App\Models\Claim;
use App\Services\Claims\ClaimSlaService;
use Illuminate\Console\Command;

class CheckClaimSlaCommand extends Command
{
    protected $signature = 'claims:check-sla';

    protected $description = 'Проверка нарушений SLA для претензий';

    public function handle(ClaimSlaService $slaService): int
    {
        $this->info('Проверка SLA для претензий...');

        $claims = Claim::query()
            ->whereNull('resolved_at')
            ->where(function ($q) {
                $q->where('sla_response_breached', false)
                    ->orWhere('sla_resolution_breached', false);
            })
            ->get();

        $breachedCount = 0;

        foreach ($claims as $claim) {
            $oldResponseBreached = $claim->sla_response_breached;
            $oldResolutionBreached = $claim->sla_resolution_breached;

            $slaService->updateSlaBreaches($claim);
            $claim->refresh();

            if (! $oldResponseBreached && $claim->sla_response_breached) {
                event(new ClaimSlaBreached($claim, 'response'));
                $breachedCount++;
                $this->warn("Нарушен SLA по ответу для претензии #{$claim->id}");
            }

            if (! $oldResolutionBreached && $claim->sla_resolution_breached) {
                event(new ClaimSlaBreached($claim, 'resolution'));
                $breachedCount++;
                $this->warn("Нарушен SLA по решению для претензии #{$claim->id}");
            }
        }

        $this->info("Проверено претензий: {$claims->count()}, нарушений: {$breachedCount}");

        return Command::SUCCESS;
    }
}
