<?php

namespace App\Events;

use App\Models\TrafficIncident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrafficIncidentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TrafficIncident $incident,
        public array $affectedZones = [],
        public array $effects = []
    ) {}
}
