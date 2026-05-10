<?php

namespace App\Events\SocialCare;

use App\Models\SocialCareEmergencyEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialCareEmergencyTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SocialCareEmergencyEvent $emergency,
    ) {}
}
