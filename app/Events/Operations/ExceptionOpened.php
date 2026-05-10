<?php

namespace App\Events\Operations;

use App\Models\Operations\OperationException;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExceptionOpened implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OperationException $exception) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(sprintf('operations.%s.exceptions', $this->exception->organization_id ?? 'global')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'exception.opened';
    }

    public function broadcastWith(): array
    {
        return [
            'exception_id' => $this->exception->id,
            'service_job_id' => $this->exception->service_job_id,
            'type' => $this->exception->exception_type,
            'severity' => $this->exception->severity,
            'status' => $this->exception->status,
        ];
    }
}

