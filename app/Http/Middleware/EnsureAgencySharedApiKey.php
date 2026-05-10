<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверка общего ключа агентов (AGENCY_AGENTS_SHARED_API_KEY / config agency-agents.office_2d.shared_api_key).
 */
class EnsureAgencySharedApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('agency-agents.office_2d.shared_api_key', '');

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'Agency shared API key is not configured (AGENCY_AGENTS_SHARED_API_KEY).',
            ], 503);
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-Agency-Shared-Key')
            ?? $request->header('X-Agency-Key');

        if (! is_string($provided) || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: invalid or missing agency API key.',
            ], 401);
        }

        return $next($request);
    }
}
