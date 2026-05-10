<?php

namespace App\Domain\Exceptions\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationExceptionBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string|int|null $organizationId,
        public int $exceptionId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ops.organization.{$this->organizationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'operation_exception.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'exception_id' => $this->exceptionId,
            'payload' => $this->payload,
        ];
    }
}

