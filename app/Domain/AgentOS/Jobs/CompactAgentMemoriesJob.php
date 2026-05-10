<?php

namespace App\Domain\AgentOS\Jobs;

use App\Domain\AgentOS\Models\AgentMemory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class CompactAgentMemoriesJob implements ShouldQueue, NotTenantAware
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        if (! (bool) config('agent-os.memory.compaction.enabled', true)) {
            return;
        }

        $olderThanDays = max(1, (int) config('agent-os.memory.compaction.older_than_days', 2));
        $keepRecent = max(20, (int) config('agent-os.memory.compaction.keep_recent', 60));
        $cutoff = now()->subDays($olderThanDays);

        $groups = AgentMemory::query()
            ->select('organization_id', 'tenant_id', 'run_id', 'agent_key')
            ->whereNotNull('run_id')
            ->where('created_at', '<=', $cutoff)
            ->groupBy('organization_id', 'tenant_id', 'run_id', 'agent_key')
            ->get();

        foreach ($groups as $group) {
            $rows = AgentMemory::query()
                ->where('organization_id', $group->organization_id)
                ->where('tenant_id', $group->tenant_id)
                ->where('run_id', $group->run_id)
                ->where('agent_key', $group->agent_key)
                ->orderByDesc('id')
                ->get();

            if ($rows->count() <= $keepRecent) {
                continue;
            }

            $toCompact = $rows->slice($keepRecent)->values();
            if ($toCompact->isEmpty()) {
                continue;
            }

            $summary = $toCompact
                ->take(25)
                ->map(fn (AgentMemory $memory) => sprintf(
                    '[%s][%s] %s',
                    $memory->memory_type,
                    $memory->role,
                    mb_substr((string) ($memory->summary ?: $memory->content), 0, 140)
                ))
                ->implode("\n");

            AgentMemory::query()->create([
                'organization_id' => $group->organization_id,
                'tenant_id' => $group->tenant_id,
                'run_id' => $group->run_id,
                'agent_key' => $group->agent_key,
                'scope' => 'run',
                'memory_type' => 'run_rollup',
                'role' => 'system',
                'content' => $summary !== '' ? $summary : 'Run memory rollup',
                'summary' => 'Compacted historical run memories',
                'importance' => 2,
                'tokens_estimate' => max(1, (int) ceil(mb_strlen($summary) / 4)),
                'metadata' => [
                    'compacted_memory_ids' => $toCompact->pluck('id')->values()->all(),
                    'compacted_count' => $toCompact->count(),
                    'older_than_days' => $olderThanDays,
                ],
            ]);

            AgentMemory::query()
                ->whereIn('id', $toCompact->pluck('id')->all())
                ->delete();
        }
    }
}
