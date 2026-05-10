<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Events\AgentRunEventAppended;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentRunEvent;
use App\Domain\AgentOS\Models\AgentRunThread;
use App\Domain\AgentOS\Models\AgentStep;

class AgentWorkspaceEventService
{
    /**
     * @return array<int,array{thread_key:string,title:string,sort_order:int,is_system:bool}>
     */
    protected function defaultThreads(): array
    {
        return [
            ['thread_key' => 'main', 'title' => 'Main', 'sort_order' => 10, 'is_system' => false],
            ['thread_key' => 'research', 'title' => 'Research', 'sort_order' => 20, 'is_system' => false],
            ['thread_key' => 'design', 'title' => 'Design', 'sort_order' => 30, 'is_system' => false],
            ['thread_key' => 'content', 'title' => 'Content', 'sort_order' => 40, 'is_system' => false],
            ['thread_key' => 'code', 'title' => 'Code', 'sort_order' => 50, 'is_system' => false],
            ['thread_key' => 'qa', 'title' => 'QA', 'sort_order' => 60, 'is_system' => false],
            ['thread_key' => 'artifacts', 'title' => 'Artifacts', 'sort_order' => 70, 'is_system' => false],
            ['thread_key' => 'logs', 'title' => 'Logs', 'sort_order' => 80, 'is_system' => true],
        ];
    }

    public function ensureThreads(AgentRun $run): void
    {
        foreach ($this->defaultThreads() as $thread) {
            AgentRunThread::query()->firstOrCreate(
                ['run_id' => $run->id, 'thread_key' => $thread['thread_key']],
                [
                    'organization_id' => $run->organization_id,
                    'tenant_id' => $run->tenant_id,
                    'title' => $thread['title'],
                    'sort_order' => $thread['sort_order'],
                    'is_system' => $thread['is_system'],
                ]
            );
        }
    }

    public function append(
        AgentRun $run,
        string $eventType,
        string $message,
        string $threadKey = 'main',
        ?AgentStep $step = null,
        array $payload = [],
        ?string $actorType = 'system',
        ?string $actorKey = null,
        ?string $eventLevel = null,
        ?string $dedupeKey = null,
        ?int $createdBy = null,
    ): AgentRunEvent {
        $this->ensureThreads($run);

        $thread = AgentRunThread::query()
            ->where('run_id', $run->id)
            ->where('thread_key', $threadKey)
            ->first();

        if (! $thread) {
            $thread = AgentRunThread::query()->create([
                'run_id' => $run->id,
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'thread_key' => $threadKey,
                'title' => ucfirst($threadKey),
                'sort_order' => 999,
                'is_system' => true,
            ]);
        }

        if ($dedupeKey) {
            $existing = AgentRunEvent::query()
                ->where('run_id', $run->id)
                ->where('dedupe_key', $dedupeKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $event = AgentRunEvent::query()->create([
            'run_id' => $run->id,
            'step_id' => $step?->id,
            'thread_id' => $thread->id,
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'event_type' => $eventType,
            'event_level' => $eventLevel,
            'actor_type' => $actorType,
            'actor_key' => $actorKey,
            'message' => $message,
            'payload' => $payload,
            'dedupe_key' => $dedupeKey,
            'created_by' => $createdBy,
        ]);

        event(new AgentRunEventAppended(
            organizationId: (string) ($run->organization_id ?? 'global'),
            runId: (int) $run->id,
            payload: [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'thread_key' => $thread->thread_key,
                'message' => $event->message,
                'actor_type' => $event->actor_type,
                'actor_key' => $event->actor_key,
                'event_level' => $event->event_level,
                'step_id' => $event->step_id,
                'payload' => $event->payload,
                'created_at' => optional($event->created_at)->toIso8601String(),
            ]
        ));

        return $event;
    }

    public function threadKeyForStepType(string $stepType): string
    {
        return match ($stepType) {
            'browser_audit', 'page_discovery', 'preview_capture', 'before_after_evidence' => 'design',
            'research_benchmark' => 'research',
            'content_redesign' => 'content',
            'target_resolution', 'content_update_execution', 'template_or_code_patch_execution', 'implementation_patch_plan', 'validation' => 'code',
            'image_generation', 'image_update_execution' => 'design',
            'quality_validation_bundle', 'testing_cicd_review' => 'qa',
            'final_delivery_package' => 'artifacts',
            default => 'main',
        };
    }

    public function actorKeyForStepType(string $stepType): string
    {
        return match ($stepType) {
            'browser_audit', 'page_discovery', 'preview_capture', 'before_after_evidence' => 'Browser Agent',
            'research_benchmark' => 'Research Agent',
            'content_redesign' => 'Content Agent',
            'target_resolution', 'content_update_execution', 'template_or_code_patch_execution', 'implementation_patch_plan', 'validation' => 'Code Agent',
            'image_generation', 'image_update_execution' => 'Image Agent',
            'quality_validation_bundle' => 'QA Agent',
            default => 'Director',
        };
    }
}
