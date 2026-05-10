<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Events\AgentMemoryAppended;
use App\Domain\AgentOS\Models\AgentMemory;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;

class AgentMemoryBankService
{
    public function remember(array $payload): AgentMemory
    {
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            $content = 'n/a';
        }

        $summary = $payload['summary'] ?? $this->summarize($content);
        $organizationId = $payload['organization_id'] ?? null;
        $tenantId = $payload['tenant_id'] ?? null;
        $runId = $payload['run_id'] ?? null;
        $stepId = $payload['step_id'] ?? null;
        $agentKey = (string) ($payload['agent_key'] ?? 'coordinator');
        $scope = (string) ($payload['scope'] ?? 'agent');
        $memoryType = (string) ($payload['memory_type'] ?? 'note');
        $role = (string) ($payload['role'] ?? 'system');

        $dedupWindow = max(0, (int) config('agent-os.memory.dedup_window_seconds', 120));
        $shouldDedup = ! in_array($memoryType, ['chat_user'], true);
        if ($dedupWindow > 0 && $shouldDedup) {
            $recentDuplicate = AgentMemory::query()
                ->where('organization_id', $organizationId)
                ->where('tenant_id', $tenantId)
                ->where('run_id', $runId)
                ->where('step_id', $stepId)
                ->where('agent_key', $agentKey)
                ->where('scope', $scope)
                ->where('memory_type', $memoryType)
                ->where('role', $role)
                ->where('content', $content)
                ->where('created_at', '>=', now()->subSeconds($dedupWindow))
                ->latest('id')
                ->first();

            if ($recentDuplicate) {
                return $recentDuplicate;
            }
        }

        $memory = AgentMemory::query()->create([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'run_id' => $runId,
            'step_id' => $stepId,
            'agent_key' => $agentKey,
            'scope' => $scope,
            'memory_type' => $memoryType,
            'role' => $role,
            'content' => $content,
            'summary' => $summary,
            'importance' => (int) ($payload['importance'] ?? 3),
            'tokens_estimate' => $payload['tokens_estimate'] ?? $this->estimateTokens($content),
            'metadata' => $payload['metadata'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ]);

        event(new AgentMemoryAppended(
            organizationId: (string) ($memory->organization_id ?? 'global'),
            runId: $memory->run_id ? (int) $memory->run_id : null,
            payload: [
                'memory_id' => $memory->id,
                'agent_key' => $memory->agent_key,
                'memory_type' => $memory->memory_type,
                'role' => $memory->role,
                'summary' => (string) $memory->summary,
            ]
        ));

        return $memory;
    }

    public function rememberChatMessage(
        AgentRun $run,
        string $agentKey,
        string $role,
        string $content,
        ?int $createdBy = null,
        array $metadata = []
    ): AgentMemory {
        return $this->remember([
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'run_id' => $run->id,
            'agent_key' => $agentKey,
            'scope' => 'run',
            'memory_type' => str_starts_with($role, 'user') ? 'chat_user' : 'chat_system',
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'created_by' => $createdBy,
            'importance' => 4,
        ]);
    }

    public function rememberStepSummary(AgentRun $run, AgentStep $step, string $content, array $metadata = []): AgentMemory
    {
        return $this->remember([
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'run_id' => $run->id,
            'step_id' => $step->id,
            'agent_key' => 'worker:'.$step->step_type,
            'scope' => 'run',
            'memory_type' => 'step_summary',
            'role' => 'worker',
            'content' => $content,
            'metadata' => array_merge([
                'step_type' => $step->step_type,
                'step_name' => $step->name,
            ], $metadata),
            'importance' => 3,
        ]);
    }

    public function buildContext(?string $organizationId, ?int $tenantId, string $agentKey, int $limit = 8): string
    {
        $maxLimit = max(1, (int) config('agent-os.memory.context_limit', 30));
        $limit = max(1, min($limit, $maxLimit));

        $query = AgentMemory::query()
            ->where('agent_key', $agentKey)
            ->where(function ($q) use ($organizationId, $tenantId): void {
                $q->where('organization_id', $organizationId)
                    ->where('tenant_id', $tenantId);
            })
            ->orderByDesc('id')
            ->limit($limit * 4);

        $rows = $query->get()
            ->sortByDesc(fn (AgentMemory $memory) => $this->decayedImportance($memory))
            ->take($limit)
            ->sortBy('id')
            ->values();
        if ($rows->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = sprintf(
                '[%s][%s] %s',
                $row->memory_type,
                $row->role,
                (string) ($row->summary ?: $this->summarize((string) $row->content))
            );
        }

        return implode("\n", $lines);
    }

    public function buildRunContext(AgentRun $run, string $agentKey, int $limit = 8): string
    {
        $maxLimit = max(1, (int) config('agent-os.memory.context_limit', 30));
        $limit = max(1, min($limit, $maxLimit));
        $rows = AgentMemory::query()
            ->where('run_id', $run->id)
            ->where('agent_key', $agentKey)
            ->orderByDesc('id')
            ->limit($limit * 4)
            ->get()
            ->sortByDesc(fn (AgentMemory $memory) => $this->decayedImportance($memory))
            ->take($limit)
            ->sortBy('id')
            ->values();

        if ($rows->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = sprintf(
                '[%s][%s] %s',
                $row->memory_type,
                $row->role,
                (string) ($row->summary ?: $this->summarize((string) $row->content))
            );
        }

        return implode("\n", $lines);
    }

    protected function summarize(string $content): string
    {
        $plain = preg_replace('/\s+/', ' ', trim($content)) ?: 'n/a';

        return mb_substr($plain, 0, 300);
    }

    protected function estimateTokens(string $content): int
    {
        $len = mb_strlen($content);

        return (int) max(1, ceil($len / 4));
    }

    protected function decayedImportance(AgentMemory $memory): float
    {
        $importance = (float) ($memory->importance ?? 1);
        $ageHours = max(0.0, (float) optional($memory->created_at)->diffInHours(now()) ?? 0.0);
        $decay = $ageHours / 48.0;

        return max(0.1, $importance - $decay);
    }
}
