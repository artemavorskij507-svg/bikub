<?php

namespace App\Domain\AgentOS\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentMemoryAppended implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public string $organizationId,
        public ?int $runId,
        public array $payload = [],
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("agent-os.organization.{$this->organizationId}"),
        ];

        if ($this->runId !== null) {
            $channels[] = new PrivateChannel("agent-os.run.{$this->runId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'memory.appended';
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

