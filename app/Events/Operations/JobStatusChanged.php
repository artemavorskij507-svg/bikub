<?php

namespace App\Events\Operations;

use App\Models\Operations\ServiceJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ServiceJob $job,
        public ?string $fromStatus,
        public string $toStatus
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel(sprintf('operations.%s.%s', $this->job->organization_id ?? 'global', $this->job->service_domain ?? 'core'))];
    }

    public function broadcastAs(): string
    {
        return 'job.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'service_job_id' => $this->job->id,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
        ];
    }
}

