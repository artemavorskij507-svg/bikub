<?php

namespace App\Events\Operations;

use App\Models\Operations\ServiceJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispatchRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ServiceJob $serviceJob,
        public string $mode = 'auto_assign'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->channelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dispatch.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'service_job_id' => $this->serviceJob->id,
            'organization_id' => $this->serviceJob->organization_id,
            'service_domain' => $this->serviceJob->service_domain,
            'mode' => $this->mode,
            'status' => $this->serviceJob->status,
        ];
    }

    private function channelName(): string
    {
        return sprintf(
            'operations.%s.%s',
            $this->serviceJob->organization_id ?? 'global',
            $this->serviceJob->service_domain
        );
    }
}

