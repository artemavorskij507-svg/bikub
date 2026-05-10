<?php

namespace App\Http\Middleware;

use App\Services\Security\UserSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackUserSession
{
    public function __construct(
        protected UserSessionService $sessionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            try {
                $this->sessionService->touch($request->user(), $request);
            } catch (Throwable $e) {
                // Session tracking must never break page/API responses.
                Log::warning('TrackUserSession failed; request continues.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
