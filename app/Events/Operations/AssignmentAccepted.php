<?php

namespace App\Events\Operations;

use App\Models\Operations\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Assignment $assignment) {}

    public function broadcastOn(): array
    {
        $job = $this->assignment->serviceJob;

        return [new PrivateChannel(sprintf('operations.%s.%s', $job?->organization_id ?? 'global', $job?->service_domain ?? 'core'))];
    }

    public function broadcastAs(): string
    {
        return 'assignment.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'service_job_id' => $this->assignment->service_job_id,
            'executor_id' => $this->assignment->executor_id,
            'status' => $this->assignment->status,
        ];
    }
}

