<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIGenerationController extends Controller
{
    /**
     * Генерация описания объявления по заголовку (через OpenAI).
     */
    public function generateDescription(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'min:3'],
        ]);

        $openaiKey = config('services.openai.key');
        if (! $openaiKey) {
            return response()->json(['error' => 'AI Service not configured'], 503);
        }

        $title = $request->string('title')->toString();
        $category = $request->input('category', 'General');

        try {
            $resp = Http::withToken($openaiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant writing classified ad descriptions in Norwegian. Be professional yet engaging.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Write a 3-sentence description for selling: '{$title}' in category '{$category}'.",
                        ],
                    ],
                ]);

            if ($resp->successful()) {
                $description = $resp->json()['choices'][0]['message']['content'] ?? '';

                return response()->json([
                    'description' => $description,
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['error' => 'Generation failed'], 500);
    }
}
