<?php

namespace App\Jobs\Partners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class TestWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $partnerId) {}

    public function handle(): void
    {
        $p = \App\Models\Partner::find($this->partnerId);
        if (!$p || !$p->webhook_url) {
            return;
        }

        try {
            Http::timeout(5)->asJson()->post($p->webhook_url, [
                'event' => 'partner.webhook.test',
                'timestamp' => now()->toIso8601String(),
                'partner' => [
                    'id' => $p->id,
                    'name' => $p->name,
                ],
            ]);
        } catch (\Throwable $e) {
            // Swallow errors for test ping
        }
    }
}

<?php

namespace App\Jobs\Partners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class TestWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $partnerId) {}

    public function handle(): void
    {
        $p = \App\Models\Partner::find($this->partnerId);
        if (!$p || !$p->webhook_url) {
            return;
        }

        @Http::timeout(5)->asJson()->post($p->webhook_url, [
            'event' => 'partner.webhook.test',
            'timestamp' => now()->toIso8601String(),
            'partner' => ['id' => $p->id, 'name' => $p->name],
        ]);
    }
}

<?php

namespace App\Jobs\Partners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class TestWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $partnerId) {}

    public function handle(): void
    {
        $p = \App\Models\Partner::find($this->partnerId);
        if (!$p || !$p->webhook_url) return;

        @Http::timeout(5)->asJson()->post($p->webhook_url, [
            'event' => 'partner.webhook.test',
            'timestamp' => now()->toIso8601String(),
            'partner' => ['id' => $p->id, 'name' => $p->name],
        ]);
    }
}


