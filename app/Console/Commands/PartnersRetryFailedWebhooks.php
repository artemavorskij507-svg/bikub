<?php

namespace App\Console\Commands;

use App\Jobs\Partners\DispatchPartnerWebhookJob;
use App\Models\PartnerWebhookLog;
use Illuminate\Console\Command;

class PartnersRetryFailedWebhooks extends Command
{
    protected $signature = 'partners:retry-failed-webhooks';

    protected $description = 'Retry failed partner webhooks that are due';

    public function handle(): int
    {
        $q = PartnerWebhookLog::where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->limit(200)
            ->get();
        foreach ($q as $log) {
            dispatch(new DispatchPartnerWebhookJob($log->id))->onQueue('webhooks');
        }
        $this->info('Queued '.count($q).' webhook retries');

        return self::SUCCESS;
    }
}
