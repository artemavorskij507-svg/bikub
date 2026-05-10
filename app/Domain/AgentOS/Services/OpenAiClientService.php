<?php

namespace App\Domain\AgentOS\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClientService
{
    public function generate(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => 'gpt-4o',
            'messages' => $messages,
            'temperature' => 0.2,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI Error', ['body' => $response->json()]);
            return [
                'ok' => false,
                'content' => '',
                'tool_calls' => [],
                'error' => $response->json('error.message', 'Unknown API Error'),
            ];
        }

        $message = $response->json('choices.0.message');

        return [
            'ok' => true,
            'content' => $message['content'] ?? '',
            'tool_calls' => $message['tool_calls'] ?? [],
        ];
    }
}
