<?php

namespace App\Domain\Ops\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkbenchActionPerformed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int|string|null $organizationId,
        public int $actorUserId,
        public string $action,
        public bool $success,
        public ?int $jobId = null,
        public ?int $executorId = null,
        public ?int $exceptionId = null,
        public ?string $message = null,
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ops.organization.{$this->organizationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'workbench.action.performed';
    }

    public function broadcastWith(): array
    {
        return [
            'actor_user_id' => $this->actorUserId,
            'action' => $this->action,
            'success' => $this->success,
            'job_id' => $this->jobId,
            'executor_id' => $this->executorId,
            'exception_id' => $this->exceptionId,
            'message' => $this->message,
            'payload' => $this->payload,
            'happened_at' => now()->toIso8601String(),
        ];
    }
}
