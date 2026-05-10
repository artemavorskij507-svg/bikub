<?php

namespace App\Console\Commands;

use App\Events\AdExpiredEvent;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Console\Command;

class CheckClassifiedsExpiration extends Command
{
    /**
     * Название консольной команды.
     *
     * @var string
     */
    protected $signature = 'classifieds:check-expiration';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Expire published classifieds past their validity date and notify external systems';

    public function handle(): int
    {
        $count = 0;

        ClassifiedAd::query()
            ->where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->chunk(100, function ($ads) use (&$count) {
                foreach ($ads as $ad) {
                    $ad->update(['status' => 'expired']);
                    AdExpiredEvent::dispatch($ad);
                    $count++;
                }
            });

        $this->info("Checked expiration: {$count} ads expired.");

        return self::SUCCESS;
    }
}
