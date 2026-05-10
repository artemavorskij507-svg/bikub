<?php

namespace App\Modules\Classifieds\Services;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIModerationService
{
    /**
     * Анализирует объявление на безопасность контента.
     * Возвращает массив: ['is_safe' => bool, 'reason' => ?string]
     */
    public function analyze(ClassifiedAd $ad): array
    {
        $text = trim(($ad->title ?? '')."\n\n".($ad->description ?? ''));

        // 1. Фолбэк на OpenAI (облако)
        $openaiKey = config('services.openai.key');
        if (! empty($openaiKey)) {
            try {
                $resp = Http::withToken($openaiKey)
                    ->timeout(5)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a moderation API. Return valid JSON only: {"is_safe": bool, "reason": string|null}.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $text,
                            ],
                        ],
                        'response_format' => ['type' => 'json_object'],
                    ]);

                if ($resp->successful()) {
                    $content = $resp->json()['choices'][0]['message']['content'] ?? '{}';
                    $data = json_decode($content, true) ?: [];

                    return [
                        'is_safe' => (bool) ($data['is_safe'] ?? true),
                        'reason' => $data['reason'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('OpenAI moderation failed: '.$e->getMessage());
            }
        }

        // Если AI не сработал — не блокируем пользователя, считаем объявление безопасным
        return ['is_safe' => true, 'reason' => null];
    }
}
