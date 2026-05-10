<?php

namespace App\Domain\AgentOS\Jobs;

use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentMemory;
use App\Domain\AgentOS\Services\AgentMemoryBankService;
use App\Domain\AgentOS\Services\AgentRunSummaryService;
use App\Domain\AgentOS\Services\AgentWorkspaceEventService;
use App\Domain\AgentOS\Services\RunOrchestratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Throwable;

class ProcessAgentRunJob implements ShouldQueue, NotTenantAware
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(
        public int $runId,
        public ?int $actorId = null,
    ) {
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('agent-os-run-'.$this->runId))->expireAfter(900)->releaseAfter(5),
        ];
    }

    /**
     * @return array<int,int>
     */
    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(
        RunOrchestratorService $orchestrator,
        AgentMemoryBankService $memoryBankService,
        AgentRunSummaryService $summaryService,
        AgentWorkspaceEventService $workspaceEventService
    ): void {
        $jobStartedAt = microtime(true);
        $run = AgentRun::query()->find($this->runId);
        if (! $run) {
            return;
        }

        if (in_array((string) $run->status, ['completed', 'blocked', 'failed', 'ready_for_review', 'audit_completed', 'followup_required'], true)) {
            return;
        }

        $createdAtMs = optional($run->created_at)->getPreciseTimestamp(3) ?? now()->getPreciseTimestamp(3);

        Log::info('AgentOS ProcessAgentRunJob started', [
            'run_id' => $this->runId,
            'status_before' => (string) $run->status,
            'connection' => (string) ($this->connection ?? config('agent-os.chat.connection', 'redis')),
            'queue' => (string) ($this->queue ?? config('agent-os.chat.queue', 'default')),
            'time_to_executing_ms' => (int) round(max(0, now()->getPreciseTimestamp(3) - $createdAtMs)),
        ]);

        $lockKey = 'agent-os:run:lock:'.$run->id;
        $lock = Cache::lock($lockKey, 900);
        if (! $lock->get()) {
            $this->release(5);

            return;
        }

        try {
            $run = $orchestrator
                ->run($run, ['actor_id' => $this->actorId])
                ->fresh(['steps', 'artifacts', 'validations']);
        } catch (Throwable $e) {
            Log::error('AgentOS ProcessAgentRunJob failed during orchestration', [
                'run_id' => $this->runId,
                'actor_id' => $this->actorId,
                'error' => $e->getMessage(),
                'type' => 'orchestrator_runtime_failure',
            ]);

            $memoryBankService->remember([
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'run_id' => $run->id,
                'agent_key' => 'coordinator',
                'scope' => 'run',
                'memory_type' => 'system_error',
                'role' => 'system',
                'content' => 'Run orchestration error: '.$e->getMessage(),
                'metadata' => [
                    'error_type' => 'orchestrator_runtime_failure',
                ],
                'created_by' => $this->actorId,
                'importance' => 5,
            ]);

            throw $e;
        } finally {
            $lock->release();
        }

        $summarySnapshot = $summaryService->build($run);

        $finalArtifact = $run->artifacts
            ->whereIn('artifact_type', ['final_delivery_package', 'final_audit_report'])
            ->sortByDesc('id')
            ->first();

        $summary = "Run {$run->id} finished with status {$run->status}.";
        if ($finalArtifact) {
            $summary .= "\n".mb_substr((string) $finalArtifact->content, 0, 1500);
        }

        $alreadyPublishedFinal = AgentMemory::query()
            ->where('run_id', $run->id)
            ->where('agent_key', 'coordinator')
            ->where('memory_type', 'chat_system')
            ->where('role', 'assistant')
            ->where('content', 'like', sprintf('Run %d finished with status%%', $run->id))
            ->exists();

        if (! $alreadyPublishedFinal) {
            $memoryBankService->remember([
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'run_id' => $run->id,
                'agent_key' => 'coordinator',
                'scope' => 'run',
                'memory_type' => 'final_report',
                'role' => 'assistant',
                'content' => $summary,
                'metadata' => [
                    'status' => $run->status,
                    'terminal_reason' => $run->terminal_reason,
                    'final_artifact_id' => $finalArtifact?->id,
                    'summary' => $summarySnapshot,
                    'message_type' => 'run_final',
                ],
                'created_by' => $this->actorId,
                'importance' => 5,
            ]);

            $memoryBankService->rememberChatMessage(
                $run,
                'coordinator',
                'assistant',
                $summary,
                $this->actorId,
                [
                    'queued' => false,
                    'status' => $run->status,
                    'terminal_reason' => $run->terminal_reason,
                    'summary' => $summarySnapshot,
                    'message_type' => 'run_final',
                ]
            );
        }

        $workspaceEventService->append(
            run: $run,
            eventType: 'run_completed',
            message: sprintf('Run %d reached terminal status `%s`.', $run->id, (string) $run->status),
            threadKey: 'main',
            step: null,
            payload: [
                'status' => $run->status,
                'terminal_reason' => $run->terminal_reason,
                'summary' => $summarySnapshot,
            ],
            actorType: 'director',
            actorKey: 'Director',
            eventLevel: in_array((string) $run->status, ['failed', 'blocked'], true) ? 'error' : 'info',
            dedupeKey: 'run_final'
        );

        Log::info('agent_os.chat.run_terminal', [
            'run_id' => $run->id,
            'status' => (string) $run->status,
            'terminal_reason' => (string) ($run->terminal_reason ?? ''),
            'time_to_terminal_ms' => (int) round(max(0, now()->getPreciseTimestamp(3) - $createdAtMs)),
            'job_duration_ms' => (int) round((microtime(true) - $jobStartedAt) * 1000),
        ]);
    }

    public function failed(Throwable $e): void
    {
        $run = AgentRun::query()->find($this->runId);
        if (! $run) {
            return;
        }

        Log::error('AgentOS ProcessAgentRunJob exhausted retries', [
            'run_id' => $this->runId,
            'actor_id' => $this->actorId,
            'error' => $e->getMessage(),
            'type' => 'job_exhausted_retries',
        ]);

        app(AgentMemoryBankService::class)->remember([
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'run_id' => $run->id,
            'agent_key' => 'coordinator',
            'scope' => 'run',
            'memory_type' => 'system_error',
            'role' => 'system',
            'content' => 'Run job failed after retries: '.$e->getMessage(),
            'metadata' => [
                'error_type' => 'job_exhausted_retries',
            ],
            'created_by' => $this->actorId,
            'importance' => 5,
        ]);
    }
}
