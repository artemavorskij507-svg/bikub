<?php

namespace App\Domain\Tracking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExecutorLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string|int|null $organizationId,
        public int $executorId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ops.organization.{$this->organizationId}"),
            new PrivateChannel("ops.executor.{$this->executorId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'executor.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'executor_id' => $this->executorId,
            'payload' => $this->payload,
        ];
    }
}

