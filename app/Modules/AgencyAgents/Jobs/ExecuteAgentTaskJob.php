<?php

namespace App\Modules\AgencyAgents\Jobs;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentTask;
use App\Modules\AgencyAgents\Services\AgentCommunicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExecuteAgentTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $taskId,
        public ?int $chiefAgentId = null
    ) {
    }

    public function handle(AgentCommunicationService $communicationService): void
    {
        $task = AgentTask::query()->find($this->taskId);
        if (! $task) {
            return;
        }

        if (! in_array($task->status, ['pending', 'in_progress'], true)) {
            return;
        }

        $agent = Agent::query()->find($task->agent_id);
        if (! $agent) {
            $task->fail('Agent not found');
            return;
        }

        if ($task->status === 'pending') {
            $task->start();
            AgentActivity::logTaskStart($agent, $task);
        }

        $agent->updateStatus('busy', 'executing_task', 'Executing assigned task');

        $apiKey = (string) config('services.openai.key', '');
        if ($apiKey === '') {
            $task->fail('OPENAI_API_KEY is missing');
            $agent->updateStatus('active', 'idle', 'Waiting for next task');
            return;
        }

        $model = (string) config('ai_assistant.model', 'gpt-4o-mini');
        $systemPrompt = $this->buildSystemPrompt($agent);

        try {
            $resp = Http::timeout(120)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => (string) $task->description],
                    ],
                    'max_tokens' => 900,
                    'temperature' => 0.2,
                ]);
        } catch (\Throwable $e) {
            Log::warning('ExecuteAgentTaskJob transport error', [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
            $task->fail('Transport error: '.$e->getMessage());
            $agent->updateStatus('active', 'idle', 'Task failed');
            return;
        }

        if (! $resp->successful()) {
            Log::warning('ExecuteAgentTaskJob http error', [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'status' => $resp->status(),
                'snippet' => substr((string) $resp->body(), 0, 400),
            ]);
            $task->fail('OpenAI HTTP error: '.$resp->status());
            $agent->updateStatus('active', 'idle', 'Task failed');
            return;
        }

        $content = trim((string) data_get($resp->json(), 'choices.0.message.content', ''));
        if ($content === '') {
            $task->fail('Empty model response');
            $agent->updateStatus('active', 'idle', 'Task failed');
            return;
        }

        $task->complete($content);
        AgentActivity::logTaskComplete($agent, $task);
        $agent->incrementTasksCompleted();
        $agent->updateStatus('active', 'idle', 'Task completed');

        if ($this->chiefAgentId) {
            $chief = Agent::query()->find($this->chiefAgentId);
            if ($chief) {
                $summary = "Execution completed by {$agent->name}\n"
                    ."Task #{$task->id}: {$task->title}\n\n"
                    .mb_substr($content, 0, 3000);

                $communicationService->sendMessage(
                    $agent,
                    $chief,
                    $summary,
                    'execution_report',
                    'high',
                    $task->id
                );
            }
        }
    }

    protected function buildSystemPrompt(Agent $agent): string
    {
        $criticalRules = trim((string) $agent->critical_rules);
        $deliverables = trim((string) $agent->technical_deliverables);
        $workflow = trim((string) $agent->workflow_process);
        $metrics = trim((string) $agent->success_metrics);

        return "You are {$agent->name}, category {$agent->category}, working in Bikube agency team.\n"
            ."You must execute the task fully, not discuss it.\n"
            ."Output in Russian and use this exact structure:\n"
            ."1) DONE\n2) CHANGED_OR_IMPLEMENTED\n3) VALIDATION\n4) RISKS\n5) HANDOFF\n"
            ."Rules:\n"
            .($criticalRules !== '' ? $criticalRules."\n" : '')
            ."Deliverables:\n"
            .($deliverables !== '' ? $deliverables."\n" : '')
            ."Workflow:\n"
            .($workflow !== '' ? $workflow."\n" : '')
            ."Success metrics:\n"
            .($metrics !== '' ? $metrics."\n" : '')
            ."Do not ask clarifying questions. If input is incomplete, make explicit assumptions and continue.";
    }
}

