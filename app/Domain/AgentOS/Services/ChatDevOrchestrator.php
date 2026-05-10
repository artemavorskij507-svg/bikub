<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;
use App\Modules\AgencyAgents\Models\Agent;
use Illuminate\Support\Facades\Log;

class ChatDevOrchestrator
{
    public function __construct(
        protected OpenAiClientService $openAi,
        protected AgentMemoryBankService $memoryService,
        protected AgentWorkspaceEventService $eventService
    ) {}

    public function processStep(AgentRun $run, AgentStep $step): array
    {
        $agentRole = (string) $step->step_type; // we match step_type to Agent slug roughly
        
        $agent = Agent::where('slug', 'like', "%{$agentRole}%")
            ->orWhere('name', 'like', "%{$agentRole}%")
            ->first();

        $systemPrompt = "You are an autonomous AI Agent execution engine.\n";
        if ($agent) {
            $systemPrompt .= "Role: {$agent->name}\n";
            $systemPrompt .= "Identity: {$agent->identity_memory}\n";
            $systemPrompt .= "Rules: {$agent->critical_rules}\n";
        } else {
            $systemPrompt .= "Role: General AI Orchestrator\n";
        }

        $systemPrompt .= "Project Root: " . base_path() . "\n";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Task Instruction: " . ($step->input_payload['instruction'] ?? $run->goal)],
        ];

        // Append previous tool outputs or step execution context if available
        $history = $step->metadata['llm_history'] ?? [];
        $tools = $this->getTools();

        $iteration = 0;
        $maxIterations = 5;

        while ($iteration < $maxIterations) {
            $iteration++;
            $currentMessages = array_merge($messages, $history);
            
            $response = $this->openAi->generate($currentMessages, $tools);

            if (!$response['ok']) {
                return [
                    'can_continue' => false,
                    'status' => 'failed',
                    'reason' => 'LLM API Error: ' . $response['error'],
                ];
            }

            $newMsg = ['role' => 'assistant', 'content' => $response['content'] ?? ''];
            if (!empty($response['tool_calls'])) {
                $newMsg['tool_calls'] = $response['tool_calls'];
            }
            $history[] = $newMsg;

            if (!empty($response['tool_calls'])) {
                $toolCallPayload = $this->handleToolCalls($response['tool_calls'], $history, $run, $step, $agent);
                if ($toolCallPayload['status'] === 'approval_required') {
                    $meta = $step->metadata ?? [];
                    $meta['llm_history'] = $toolCallPayload['history'];
                    $meta['pending_tool_calls'] = $toolCallPayload['pending_tool_calls'];
                    $step->metadata = $meta;
                    $step->save();
                    
                    return $toolCallPayload;
                }
                
                // For safe tools, append to history and continue loop
                $history = $toolCallPayload['history'];
            } else {
                return [
                    'can_continue' => true,
                    'status' => 'completed',
                    'content' => (string)$response['content'],
                    'history' => $history,
                ];
            }
        }

        return [
            'can_continue' => true,
            'status' => 'completed',
            'content' => "Остановлено: Достигнут лимит локальных вызова инструментов (5).\n\n" . ($response['content'] ?? ''),
            'history' => $history,
        ];
    }

    protected function handleToolCalls(array $toolCalls, array $history, AgentRun $run, AgentStep $step, ?Agent $agent): array
    {
        $requiresApproval = false;
        $toolResults = [];

        foreach ($toolCalls as $call) {
            $fnName = $call['function']['name'];
            $args = json_decode($call['function']['arguments'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $toolResults[] = [
                    'tool_call_id' => $call['id'],
                    'role' => 'tool',
                    'name' => $fnName,
                    'content' => "JSON Parse Error in arguments. Please fix your tool arguments format.",
                ];
                continue;
            }

            if (in_array($fnName, ['write_file', 'execute_command'])) {
                $requiresApproval = true;
                break;
            }

            if ($fnName === 'read_file') {
                $path = base_path($args['path'] ?? '');
                $realPath = realpath($path);
                
                if ($realPath === false || !str_starts_with($realPath, realpath(base_path()))) {
                    $content = "Error: Invalid path. Path must be inside project root.";
                } else {
                    $content = file_exists($realPath) ? file_get_contents($realPath) : "Error: File not found.";
                }
                
                $toolResults[] = [
                    'tool_call_id' => $call['id'],
                    'role' => 'tool',
                    'name' => $fnName,
                    'content' => substr($content, 0, 8000), // Prevent context limit blowup
                ];
                
                if ($agent) {
                    $this->eventService->append(
                        run: $run,
                        eventType: 'tool_action',
                        message: "{$agent->name} read file: {$args['path']}",
                        threadKey: 'main',
                        step: $step,
                        payload: ['path' => $args['path']],
                        actorType: 'agent',
                        actorKey: $agent->name,
                        eventLevel: 'info'
                    );
                }
            }
        }

        if ($requiresApproval) {
            return [
                'can_continue' => false,
                'status' => 'approval_required',
                'pending_tool_calls' => $toolCalls,
                'history' => $history,
                'reason' => 'Destructive action requires user approval.',
            ];
        }

        // Loop back immediately for safe tools (read_file) -- ideally we'd recurse but we'll return to RunOrchestrator to step
        foreach ($toolResults as $tr) {
            $history[] = $tr;
        }

        return [
            'can_continue' => true,
            'status' => 'tool_executed', // Or we let run loop catch this
            'history' => $history,
            'content' => 'Executed safe tools read_file', 
        ];
    }

    public function applyApprovedToolCalls(AgentStep $step): void
    {
        // Executes the previously stored tool calls after user approval
        $pending = $step->metadata['pending_tool_calls'] ?? [];
        $history = $step->metadata['llm_history'] ?? [];
        
        $results = [];
        foreach ($pending as $call) {
            $fnName = $call['function']['name'];
            $args = json_decode($call['function']['arguments'], true) ?? [];
            
            if ($fnName === 'write_file') {
                $path = base_path($args['path'] ?? '');
                $realDir = realpath(dirname($path));
                
                if ($realDir === false || !str_starts_with($realDir, realpath(base_path()))) {
                    $results[] = [
                        'tool_call_id' => $call['id'],
                        'role' => 'tool',
                        'name' => $fnName,
                        'content' => "Error: Cannot write outside project root."
                    ];
                } else {
                    file_put_contents($path, $args['content'] ?? '');
                    $results[] = [
                        'tool_call_id' => $call['id'],
                        'role' => 'tool',
                        'name' => $fnName,
                        'content' => "File written successfully.",
                    ];
                }
            } elseif ($fnName === 'execute_command') {
                $cmd = escapeshellcmd($args['command']);
                $output = shell_exec("cd " . base_path() . " && " . $cmd);
                $results[] = [
                    'tool_call_id' => $call['id'],
                    'role' => 'tool',
                    'name' => $fnName,
                    'content' => "Command output:\n" . substr((string)$output, 0, 4000),
                ];
            }
        }

        foreach ($results as $res) {
            $history[] = $res;
        }

        $meta = $step->metadata;
        unset($meta['pending_tool_calls']);
        $meta['llm_history'] = $history;
        $step->metadata = $meta;
        $step->save();
    }

    protected function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'read_file',
                    'description' => 'Reads a local file relative to project root.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'path' => ['type' => 'string']
                        ],
                        'required' => ['path']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'write_file',
                    'description' => 'Writes content to a file (requires user approval).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'path' => ['type' => 'string'],
                            'content' => ['type' => 'string']
                        ],
                        'required' => ['path', 'content']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'execute_command',
                    'description' => 'Executes a bash/artisan command in the project root (requires user approval).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'command' => ['type' => 'string']
                        ],
                        'required' => ['command']
                    ]
                ]
            ]
        ];
    }
}
