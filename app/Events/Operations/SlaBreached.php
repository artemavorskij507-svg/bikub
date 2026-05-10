<?php

namespace App\Events\Operations;

use App\Models\Operations\ServiceJob;
use App\Models\Operations\SlaTimer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaBreached implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ServiceJob $serviceJob,
        public string $phase,
        public SlaTimer $timer
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(sprintf('operations.%s.sla', $this->serviceJob->organization_id ?? 'global')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sla.breached';
    }

    public function broadcastWith(): array
    {
        return [
            'service_job_id' => $this->serviceJob->id,
            'phase' => $this->phase,
            'states' => [
                'dispatch' => $this->timer->dispatch_state,
                'arrival' => $this->timer->arrival_state,
                'completion' => $this->timer->completion_state,
            ],
        ];
    }
}

