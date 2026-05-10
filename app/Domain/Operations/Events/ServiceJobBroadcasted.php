<?php

namespace App\Domain\Operations\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceJobBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string|int|null $organizationId,
        public int $jobId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ops.organization.{$this->organizationId}"),
            new PrivateChannel("ops.job.{$this->jobId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'service_job.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'payload' => $this->payload,
        ];
    }
}

