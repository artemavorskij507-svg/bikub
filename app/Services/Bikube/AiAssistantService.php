<?php

namespace App\Services\Bikube;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    public function generateReply(AssistantConversation $conv, array $options = []): AssistantMessage
    {
        $systemPrompt = config('ai_assistant.system_prompt');
        $model = config('ai_assistant.model');

        // build messages history (limit tokens in production)
        $history = $conv->messages()->orderBy('id')->get()->map(function ($m) {
            return [
                'role' => $m->role === 'assistant' ? 'assistant' : ($m->role === 'system' ? 'system' : 'user'),
                'content' => $m->content,
            ];
        })->toArray();

        array_unshift($history, ['role' => 'system', 'content' => $systemPrompt]);

        // call provider (OpenAI JSON API)
        $apiKey = config('ai_assistant.api_key') ?? env('OPENAI_API_KEY');

        $resp = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $history,
                'max_tokens' => config('ai_assistant.max_tokens', 1024),
            ]);

        if (! $resp->ok()) {
            Log::error('AI assistant error', ['status' => $resp->status(), 'body' => $resp->body()]);
            $fallback = 'Извините, ассистент временно недоступен.';

            return AssistantMessage::create([
                'assistant_conversation_id' => $conv->id,
                'role' => 'assistant',
                'content' => $fallback,
                'from_ai' => true,
            ]);
        }

        $data = $resp->json();
        $reply = $data['choices'][0]['message']['content'] ?? ($data['choices'][0]['text'] ?? '');

        return AssistantMessage::create([
            'assistant_conversation_id' => $conv->id,
            'role' => 'assistant',
            'content' => trim($reply),
            'from_ai' => true,
        ]);
    }
}
