<?php

namespace App\Domain\AgentOS\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRunProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public string $organizationId,
        public int $runId,
        public array $payload = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("agent-os.organization.{$this->organizationId}"),
            new PrivateChannel("agent-os.run.{$this->runId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'run.progress.updated';
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->runId,
            'payload' => $this->payload,
        ];
    }
}

