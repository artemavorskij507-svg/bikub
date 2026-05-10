<?php

namespace App\Listeners;

use App\Events\ClaimRejected;
use App\Events\ClaimResolved;
use App\Notifications\ClaimStatusChangedForCustomer;

class NotifyCustomerAboutClaimStatus
{
    public function handle(ClaimResolved|ClaimRejected $event): void
    {
        $claim = $event->claim->load('user');
        $user = $claim->user;

        if (! $user) {
            return;
        }

        $user->notify(new ClaimStatusChangedForCustomer($claim));
    }
}
