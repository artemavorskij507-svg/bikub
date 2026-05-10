<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Services\Partners\PartnerWebhookDispatcher;
use Illuminate\Console\Command;

class PartnersWebhookTest extends Command
{
    protected $signature = 'partners:webhook:test {partner_id} {event_type=partner.webhook.test}';

    protected $description = 'Send a test webhook to partner and log it';

    public function handle(): int
    {
        $partner = Partner::find($this->argument('partner_id'));
        if (! $partner) {
            $this->error('Partner not found');

            return self::FAILURE;
        }
        app(PartnerWebhookDispatcher::class)->dispatch($partner, $this->argument('event_type'), [
            'event' => $this->argument('event_type'),
            'timestamp' => now()->toIso8601String(),
            'partner' => ['id' => $partner->id, 'name' => $partner->name],
        ]);
        $this->info('Test webhook queued');

        return self::SUCCESS;
    }
}
