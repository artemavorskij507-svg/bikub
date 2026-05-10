<?php

namespace App\Filament\Pages;

use App\Domain\AgentOS\Actions\StartAgentRunAction;
use App\Domain\AgentOS\Jobs\ProcessAgentRunJob;
use App\Domain\AgentOS\Models\AgentArtifact;
use App\Domain\AgentOS\Models\AgentMemory;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentRunEvent;
use App\Domain\AgentOS\Models\AgentRunThread;
use App\Domain\AgentOS\Services\AgentMemoryBankService;
use App\Domain\AgentOS\Services\AgentRunSummaryService;
use App\Domain\AgentOS\Services\AgentWorkspaceEventService;
use App\Domain\AgentOS\Services\RunOrchestratorService;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Services\AgentCommunicationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AgentTeamChat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-alt-2';

    protected static ?string $navigationLabel = 'Team Chat';

    protected static ?string $title = 'AI Agent Team Chat';

    protected static string $view = 'filament.pages.agent-team-chat';

    public string $message = '';

    public ?int $activeRunId = null;

    public ?string $activeRunStatus = null;

    public bool $showSystemMessages = false;

    public string $selectedThreadKey = 'main';

    /** @var array<string,mixed>|null */
    public ?array $activeRunSummary = null;

    /** @var array<string,mixed> */
    public array $health = [];

    /** @var array<int,array<string,mixed>> */
    public array $workspaceThreads = [];

    /** @var array<int,array<string,mixed>> */
    public array $workspaceEvents = [];

    /** @var array<int,array<string,mixed>> */
    public array $workspaceArtifacts = [];

    /** @var array<int,string> */
    public array $activeTeam = [];

    /** @var array<int,array<string,mixed>> */
    public array $recentRuns = [];

    protected $listeners = ['message-sent' => '$refresh'];

    public function mount(): void
    {
        $this->hydrateActiveRunState();
    }

    public function toggleSystemMessages(): void
    {
        $this->showSystemMessages = ! $this->showSystemMessages;
        $this->hydrateActiveRunState();
    }

    public function syncAgencyAgents(): void
    {
        $synchronized = app(\App\Modules\AgencyAgents\Services\AgentLibraryService::class)->syncAll();
        \Filament\Notifications\Notification::make()
            ->title("Synced {$synchronized} roles from library")
            ->success()
            ->send();
    }

    public function approveStep(int $stepId): void
    {
        $step = \App\Domain\AgentOS\Models\AgentStep::find($stepId);
        if (!$step) return;

        $orchestrator = app(\App\Domain\AgentOS\Services\ChatDevOrchestrator::class);
        $orchestrator->applyApprovedToolCalls($step);
        
        app(\App\Domain\AgentOS\Actions\UpdateAgentStepStatusAction::class)->execute($step, \App\Domain\AgentOS\Enums\AgentStepStatus::QUEUED->value);
        
        $run = $step->run;
        if ((string)$run->status === 'blocked') {
            app(\App\Domain\AgentOS\Actions\UpdateAgentRunStatusAction::class)->execute($run, 'executing');
        }
        
        $this->dispatchRunJob($run->id, auth()->id(), 'approval_granted');
        $this->hydrateActiveRunState();
    }

    public function selectThread(string $threadKey): void
    {
        $this->selectedThreadKey = $threadKey !== '' ? $threadKey : 'main';
        $this->refreshWorkspaceData();
    }

    public function selectRun(int $runId): void
    {
        $this->activeRunId = $runId;
        $this->selectedThreadKey = 'main';
        $this->hydrateActiveRunState();
    }

    public function sendMessage(): void
    {
        $startedAt = microtime(true);
        $text = $this->sanitizeUserMessage($this->message);
        if ($text === '') {
            return;
        }

        $this->message = '';

        $chief = $this->resolveChiefAgent();
        if (! $chief) {
            Notification::make()
                ->title('Координатор не найден')
                ->body('Не найден chief-agent с slug director-agent.')
                ->danger()
                ->send();

            return;
        }

        if ((bool) config('agent-os.chat.legacy_bridge_enabled', false)) {
            $proxy = $this->resolveAdminProxyAgent();
            $comm = app(AgentCommunicationService::class);

            try {
                $comm->sendMessage($proxy, $chief, $text, 'assistance_request', 'high');
            } catch (\Throwable $e) {
                Log::warning('AgentTeamChat legacy bridge failed', ['e' => $e->getMessage()]);
            }
        }

        $memoryBank = app(AgentMemoryBankService::class);

        $run = app(StartAgentRunAction::class)->execute([
            'organization_id' => auth()->user()?->organization_id,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
            'goal' => $text,
            'risk_level' => 'medium',
            'requires_approval' => false,
            'deployment_allowed' => false,
            'idempotency_key' => sha1('agent_chat|'.($chief->id ?? 0).'|'.auth()->id().'|'.mb_strtolower($text)),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'metadata' => [
                'source' => 'agent_team_chat',
            ],
        ]);

        $this->activeRunId = $run->id;
        $this->activeRunStatus = (string) $run->status;

        $memoryBank->rememberChatMessage(
            $run,
            'coordinator',
            'user',
            $text,
            auth()->id(),
            ['source' => 'agent_team_chat', 'message_type' => 'user']
        );

        app(AgentWorkspaceEventService::class)->append(
            run: $run,
            eventType: 'director_decision',
            message: 'Запрос принят и добавлен в execution loop.',
            threadKey: 'main',
            payload: ['goal' => $text],
            actorType: 'director',
            actorKey: 'Director',
            eventLevel: 'info'
        );

        $chatAsync = (bool) config('agent-os.chat.async_enabled', true);
        $syncFallback = (bool) config('agent-os.chat.sync_fallback_on_dispatch_fail', false);

        if ($chatAsync) {
            try {
                $this->dispatchRunJob($run->id, auth()->id(), 'chat_send');

                $memoryBank->rememberChatMessage(
                    $run,
                    'coordinator',
                    'assistant',
                    $this->formatQueuedRunSummary($run),
                    auth()->id(),
                    ['queued' => true, 'message_type' => 'system_ack', 'run_id' => $run->id]
                );

                Log::info('agent_os.chat.ack', [
                    'run_id' => $run->id,
                    'ack_latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'connection' => (string) config('agent-os.chat.connection', 'redis'),
                    'queue' => 'default',
                ]);
            } catch (\Throwable $e) {
                Log::warning('AgentTeamChat async dispatch failed', ['e' => $e->getMessage(), 'run_id' => $run->id]);

                if ($syncFallback) {
                    $run = app(RunOrchestratorService::class)->run($run, ['actor_id' => auth()->id()]);
                    $summary = $this->formatRunSummary($run->fresh(['steps', 'artifacts', 'validations']));
                    $memoryBank->rememberChatMessage($run, 'coordinator', 'assistant', $summary, auth()->id(), [
                        'queued' => false,
                        'fallback' => 'sync_after_dispatch_error',
                        'message_type' => 'run_final',
                        'run_id' => $run->id,
                    ]);
                } else {
                    $memoryBank->rememberChatMessage($run, 'coordinator', 'assistant', "Запрос принят.\nRun ID: {$run->id}\nОчередь временно недоступна, попробуйте повторить через несколько секунд.", auth()->id(), [
                        'queued' => false,
                        'dispatch_error' => true,
                        'message_type' => 'system_ack',
                        'run_id' => $run->id,
                    ]);
                }
            }
        } else {
            $mode = (string) config('agent-os.execution_mode', 'sync');
            if ($mode === 'sync') {
                $run = app(RunOrchestratorService::class)->run($run, ['actor_id' => auth()->id()]);
            }
            $memoryBank->rememberChatMessage($run, 'coordinator', 'assistant', $this->formatRunSummary($run->fresh(['steps', 'artifacts', 'validations'])), auth()->id(), [
                'queued' => false,
                'message_type' => 'run_final',
                'run_id' => $run->id,
            ]);
        }

        $this->hydrateActiveRunState();
        $this->emit('message-sent');

        Notification::make()
            ->title($chatAsync ? 'Run queued' : 'Run processed')
            ->success()
            ->send();
    }

    public function refreshRunProgress(): void
    {
        if (! $this->activeRunId) {
            $this->hydrateActiveRunState();
            return;
        }

        $run = AgentRun::query()->find($this->activeRunId);
        if (! $run) {
            $this->activeRunId = null;
            $this->activeRunStatus = null;
            $this->activeRunSummary = null;
            $this->workspaceThreads = [];
            $this->workspaceEvents = [];
            $this->workspaceArtifacts = [];
            $this->activeTeam = [];
            $this->health = $this->collectHealthIndicators();
            $this->loadRecentRuns();

            return;
        }

        $summary = app(AgentRunSummaryService::class)->build($run);
        $this->activeRunStatus = (string) ($summary['terminal_status'] ?? $run->status);
        $this->activeRunSummary = $summary;

        $this->refreshWorkspaceData($run);

        $planningStaleSeconds = max(5, (int) config('agent-os.chat.planning_stale_seconds', 20));
        if (
            $run->status === 'planning'
            && optional($run->updated_at)?->lte(now()->subSeconds($planningStaleSeconds))
            && $run->steps()->where('status', 'queued')->exists()
        ) {
            try {
                $this->dispatchRunJob($run->id, auth()->id(), 'planning_stale_failsafe');
            } catch (\Throwable $e) {
                Log::warning('AgentTeamChat failsafe redispatch failed', [
                    'run_id' => $run->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->health = $this->collectHealthIndicators();
        $this->loadRecentRuns();
    }

    public function getChiefAgentProperty(): ?Agent
    {
        return $this->resolveChiefAgent();
    }

    public function getActiveAgentsOnlineCountProperty(): int
    {
        $names = $this->activeTeam;
        if ($names === []) {
            return 0;
        }

        return Agent::query()
            ->where('status', 'active')
            ->whereIn('name', $names)
            ->count();
    }

    protected function resolveChiefAgent(): ?Agent
    {
        $slug = (string) config('agency-agents.chief_agent_slug', 'director-agent');

        return Agent::query()->where('slug', $slug)->first()
            ?? Agent::query()->orderBy('id')->first();
    }

    protected function resolveAdminProxyAgent(): Agent
    {
        return Agent::query()->firstOrCreate(
            ['slug' => 'admin-console-proxy'],
            [
                'name' => 'Admin Console Proxy',
                'description' => 'Bridge between admin panel and chief coordinator.',
                'category' => 'support',
                'color' => 'slate',
                'emoji' => 'console',
                'status' => 'active',
                'current_zone' => 'meeting_room',
                'position_x' => 10,
                'position_y' => 10,
                'vibe' => 'system',
            ]
        );
    }

    protected function hydrateActiveRunState(): void
    {
        $run = $this->resolveCurrentRun();

        if (! $run) {
            $this->activeRunId = null;
            $this->activeRunStatus = null;
            $this->activeRunSummary = null;
            $this->workspaceThreads = [];
            $this->workspaceEvents = [];
            $this->workspaceArtifacts = [];
            $this->activeTeam = [];
            $this->health = $this->collectHealthIndicators();
            $this->loadRecentRuns();

            return;
        }

        $summary = app(AgentRunSummaryService::class)->build($run);
        $this->activeRunId = $run->id;
        $this->activeRunStatus = (string) $run->status;
        $this->activeRunSummary = $summary;

        $this->refreshWorkspaceData($run);
        $this->health = $this->collectHealthIndicators();
        $this->loadRecentRuns();
    }

    protected function refreshWorkspaceData(?AgentRun $run = null): void
    {
        $run ??= $this->activeRunId ? AgentRun::query()->find($this->activeRunId) : null;
        if (! $run) {
            $this->workspaceThreads = [];
            $this->workspaceEvents = [];
            $this->workspaceArtifacts = [];
            $this->activeTeam = [];
            return;
        }

        app(AgentWorkspaceEventService::class)->ensureThreads($run);

        $threads = AgentRunThread::query()
            ->where('run_id', $run->id)
            ->withCount('events')
            ->orderBy('sort_order')
            ->get();

        $threadKeys = $threads->pluck('thread_key')->all();
        if (! in_array($this->selectedThreadKey, $threadKeys, true)) {
            $this->selectedThreadKey = in_array('main', $threadKeys, true) ? 'main' : ((string) ($threadKeys[0] ?? 'main'));
        }

        $this->workspaceThreads = $threads->map(fn ($thread) => [
            'id' => $thread->id,
            'thread_key' => $thread->thread_key,
            'title' => $thread->title,
            'events_count' => (int) $thread->events_count,
            'is_system' => (bool) $thread->is_system,
        ])->values()->all();

        $eventsQuery = AgentRunEvent::query()
            ->where('run_id', $run->id)
            ->with('thread:id,thread_key,title')
            ->orderByDesc('id');

        if (! $this->showSystemMessages) {
            $eventsQuery->whereNotIn('event_type', ['run_started']);
        }

        if ($this->selectedThreadKey !== '' && $this->selectedThreadKey !== 'all') {
            $eventsQuery->whereHas('thread', fn ($q) => $q->where('thread_key', $this->selectedThreadKey));
        }

        $events = $eventsQuery->limit(300)->get()->reverse()->values();
        $this->workspaceEvents = $events->map(fn ($event) => [
            'id' => $event->id,
            'thread_key' => data_get($event, 'thread.thread_key', 'main'),
            'thread_title' => data_get($event, 'thread.title', 'Main'),
            'event_type' => (string) $event->event_type,
            'event_level' => (string) ($event->event_level ?: 'info'),
            'actor_key' => (string) ($event->actor_key ?: 'System'),
            'actor_type' => (string) ($event->actor_type ?: 'system'),
            'message' => (string) ($event->message ?: ''),
            'payload' => (array) ($event->payload ?? []),
            'at' => optional($event->created_at)->format('H:i:s') ?? now()->format('H:i:s'),
        ])->all();

        $this->workspaceArtifacts = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->latest('id')
            ->limit(120)
            ->get()
            ->map(fn ($artifact) => [
                'id' => $artifact->id,
                'step_id' => $artifact->step_id,
                'artifact_type' => $artifact->artifact_type,
                'validation_status' => (string) ($artifact->validation_status ?: 'unknown'),
                'content_preview' => mb_substr((string) ($artifact->content ?? ''), 0, 200),
                'metadata' => (array) ($artifact->metadata ?? []),
                'updated_at' => optional($artifact->updated_at)->format('H:i:s') ?? now()->format('H:i:s'),
            ])
            ->values()
            ->all();

        $participationEvents = $events->filter(function ($event): bool {
            return in_array((string) $event->actor_type, ['agent', 'director'], true)
                && (string) ($event->actor_key ?? '') !== 'System'
                && in_array((string) $event->event_type, [
                    'run_started',
                    'director_decision',
                    'delegation_created',
                    'task_delegated',
                    'tool_call_started',
                    'tool_call_finished',
                    'tool_action',
                    'artifact_created',
                    'finding_detected',
                    'finding',
                    'step_completed',
                    'validation_failed',
                    'approval_required',
                    'blocked_reason',
                    'revision_requested',
                ], true);
        });

        $this->activeTeam = $participationEvents
            ->pluck('actor_key')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    protected function resolveCurrentRun(): ?AgentRun
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $query = AgentRun::query()
            ->where('organization_id', $user->organization_id)
            ->where('tenant_id', $user->tenant_id ?? null)
            ->latest('id');

        if ($this->activeRunId) {
            $active = (clone $query)->where('id', $this->activeRunId)->first();
            if ($active) {
                return $active;
            }
        }

        return (clone $query)
            ->whereIn('status', ['queued', 'planning', 'executing', 'waiting_dependencies', 'needs_revision', 'validation_failed', 'ready_for_review'])
            ->first()
            ?? (clone $query)->first();
    }

    protected function loadRecentRuns(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->recentRuns = [];
            return;
        }

        $runs = AgentRun::query()
            ->where('organization_id', $user->organization_id)
            ->where('tenant_id', $user->tenant_id ?? null)
            ->latest('id')
            ->limit(15)
            ->get(['id', 'goal', 'status', 'risk_level', 'requires_approval', 'updated_at']);

        $this->recentRuns = $runs->map(function (AgentRun $run): array {
            $summary = app(AgentRunSummaryService::class)->build($run);

            return [
                'id' => $run->id,
                'goal' => mb_substr((string) $run->goal, 0, 90),
                'status' => (string) $run->status,
                'risk_level' => (string) ($run->risk_level ?: 'medium'),
                'requires_approval' => (bool) $run->requires_approval,
                'progress_percent' => (int) ($summary['progress_percent'] ?? 0),
                'updated_at' => optional($run->updated_at)->format('H:i') ?? now()->format('H:i'),
            ];
        })->values()->all();
    }

    protected function formatRunSummary(AgentRun $run): string
    {
        $steps = $run->steps;
        $completed = $steps->where('status', 'completed')->count();
        $total = $steps->count();
        $terminal = (string) $run->status;
        $reason = (string) ($run->terminal_reason ?? 'n/a');
        $findingsCount = (int) data_get($run->metadata, 'audit_findings_count', 0);
        $followupCreated = (bool) data_get($run->metadata, 'followup_phase_created', false);

        $finalReport = $run->artifacts
            ->whereIn('artifact_type', ['final_delivery_package', 'final_audit_report'])
            ->sortByDesc('id')
            ->first();

        $preview = $finalReport
            ? mb_substr((string) $finalReport->content, 0, 1200)
            : 'Final report artifact was not generated.';

        return "Run status: {$terminal}\n"
            ."Terminal reason: {$reason}\n"
            ."Steps completed: {$completed}/{$total}\n"
            ."Findings count: {$findingsCount}\n"
            ."Follow-up created: ".($followupCreated ? 'yes' : 'no')."\n"
            ."Run ID: {$run->id}\n"
            ."\nFinal report preview:\n{$preview}";
    }

    protected function formatQueuedRunSummary(AgentRun $run): string
    {
        return "Запрос принят.\n"
            ."Run ID: {$run->id}\n"
            ."Status: queued\n"
            ."Исполнение запущено в фоне. Прогресс обновится автоматически.";
    }

    protected function sanitizeUserMessage(string $value): string
    {
        $text = trim(strip_tags($value));
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';

        return mb_substr($text, 0, 5000);
    }

    protected static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected function dispatchRunJob(int $runId, ?int $actorId, string $reason): void
    {
        $connection = (string) config('agent-os.chat.connection', 'redis');
        $queue = 'default';

        ProcessAgentRunJob::dispatch($runId, $actorId)
            ->onConnection($connection)
            ->onQueue($queue);

        Log::info('AgentTeamChat dispatched ProcessAgentRunJob', [
            'run_id' => $runId,
            'actor_id' => $actorId,
            'connection' => $connection,
            'queue' => $queue,
            'reason' => $reason,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function collectHealthIndicators(): array
    {
        $connection = (string) config('agent-os.chat.connection', 'redis');
        $queue = (string) config('agent-os.chat.queue', 'default');

        $horizonStatus = Cache::remember('agent-os:horizon-status', now()->addSeconds(10), function (): string {
            try {
                if (class_exists('Laravel\\Horizon\\Horizon')) {
                    $status = \Laravel\Horizon\Horizon::status();
                    return is_string($status) ? $status : 'running';
                }
            } catch (\Throwable) {
                // fallback below
            }

            try {
                $status = Redis::get('horizon:status');
                if (is_string($status) && $status !== '') {
                    return $status;
                }
            } catch (\Throwable) {
                // no-op
            }

            return 'unknown';
        });

        return [
            'execution_connection' => $connection,
            'queue' => $queue,
            'horizon_status' => $horizonStatus,
            'last_run_update' => (string) ($this->activeRunSummary['last_step_at'] ?? ''),
        ];
    }
}





