<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireWorkbenchIdempotencyKey
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->header('X-Idempotency-Key')) {
            return response()->json([
                'message' => 'Missing required X-Idempotency-Key header.',
            ], 422);
        }

        return $next($request);
    }
}

