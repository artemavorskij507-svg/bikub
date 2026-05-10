<?php

return [
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'system_prompt' => env('BIKUBE_ASSISTANT_DEFAULT_PROMPT', 'You are Bikube Smart Assistant. Help couriers with concise, actionable instructions. Use local Narvik context.'),
    'max_tokens' => 1024,
    'api_key' => env('OPENAI_API_KEY'),
];
