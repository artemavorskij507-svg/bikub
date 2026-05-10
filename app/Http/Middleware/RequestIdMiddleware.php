<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $rid = $request->header('X-Request-Id') ?? (string) Str::uuid();
        // attach to request attributes for later logging
        $request->attributes->set('request_id', $rid);
        // also set header for outgoing responses
        $response = $next($request);
        $response->headers->set('X-Request-Id', $rid);

        return $response;
    }
}
