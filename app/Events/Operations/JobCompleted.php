<?php

namespace App\Events\Operations;

use App\Models\Operations\ServiceJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceJob $serviceJob) {}

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
        return 'job.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'service_job_id' => $this->serviceJob->id,
            'status' => $this->serviceJob->status,
            'completed_at' => $this->serviceJob->actual_completed_at?->toIso8601String(),
        ];
    }
}

