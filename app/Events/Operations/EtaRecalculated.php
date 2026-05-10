<?php

namespace App\Events\Operations;

use App\Models\Operations\ServiceJob;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EtaRecalculated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ServiceJob $serviceJob,
        public CarbonInterface $etaAt,
        public array $meta = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(sprintf(
                'operations.%s.%s',
                $this->serviceJob->organization_id ?? 'global',
                $this->serviceJob->service_domain
            )),
        ];
    }

    public function broadcastAs(): string
    {
        return 'eta.recalculated';
    }

    public function broadcastWith(): array
    {
        return [
            'service_job_id' => $this->serviceJob->id,
            'eta_at' => $this->etaAt->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}

