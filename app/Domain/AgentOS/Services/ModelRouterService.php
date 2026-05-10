<?php

namespace App\Domain\AgentOS\Services;

use App\Models\Domain\AgentOS\Models\AgentModelConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModelRouterService
{
    protected array $modelEndpoints = [
        'opus-4.7' => 'claude-opus-4-7',
        'sonnet-4.5' => 'claude-sonnet-4-5',
        'haiku-4.5' => 'claude-haiku-4-5',
    ];

    public function callModel(int $agentId, string $prompt, array $context = []): array
    {
        $config = AgentModelConfig::where('agent_id', $agentId)->first();
        
        if (!$config) {
            $config = new AgentModelConfig([
                'model_name' => 'sonnet-4.5',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ]);
        }

        $modelId = $this->modelEndpoints[$config->model_name] ?? 'claude-sonnet-4-5';
        
        $apiKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY');
        
        if (!$apiKey) {
            Log::error('Anthropic API key not configured');
            return [
                'success' => false,
                'error' => 'API key not configured',
            ];
        }

        try {
            $messages = [
                ['role' => 'user', 'content' => $prompt]
            ];

            $systemPrompt = $config->system_prompt_override ?? $context['system_prompt'] ?? '';

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => $modelId,
                'max_tokens' => $config->max_tokens,
                'temperature' => $config->temperature,
                'messages' => $messages,
                'system' => $systemPrompt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'content' => $data['content'][0]['text'] ?? '',
                    'model' => $modelId,
                    'usage' => $data['usage'] ?? [],
                ];
            }

            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Model router exception', [
                'message' => $e->getMessage(),
                'agent_id' => $agentId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function setModelForAgent(int $agentId, string $modelName, array $config = []): AgentModelConfig
    {
        return AgentModelConfig::updateOrCreate(
            ['agent_id' => $agentId],
            array_merge([
                'model_name' => $modelName,
            ], $config)
        );
    }
}
