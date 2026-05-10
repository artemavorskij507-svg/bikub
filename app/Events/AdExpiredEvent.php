<?php

namespace App\Events;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdExpiredEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ClassifiedAd $ad,
    ) {}
}
