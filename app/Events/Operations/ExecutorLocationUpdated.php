<?php

namespace App\Events\Operations;

use App\Models\Operations\Executor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExecutorLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Executor $executor,
        public float $lat,
        public float $lng,
        public array $extra = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(sprintf('operations.%s.live', $this->executor->organization_id ?? 'global')),
        ];
    }

    public function broadcastAs(): string
    {
        return 'executor.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'executor_id' => $this->executor->id,
            'organization_id' => $this->executor->organization_id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'extra' => $this->extra,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

