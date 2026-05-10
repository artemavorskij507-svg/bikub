<?php

namespace App\Http\Middleware;

use App\Models\ApiAuditLog;
use App\Models\ApiRateLimit;
use App\Models\OauthAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OAuth2Middleware
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return $this->unauthorizedResponse('Missing access token');
        }

        $accessToken = $this->validateToken($token);

        if (! $accessToken) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        if ($scope && ! $accessToken->hasScope($scope)) {
            return $this->forbiddenResponse("Insufficient scope. Required: {$scope}");
        }

        // Rate limiting
        if (! $this->checkRateLimit($accessToken, $request)) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Rate limit exceeded',
            ], 429);
        }

        // Audit logging
        $this->logApiCall($accessToken, $request);

        // Update token last used
        $accessToken->updateLastUsed();

        // Add token info to request
        $request->merge([
            'oauth_token' => $accessToken,
            'oauth_client' => $accessToken->client,
            'oauth_partner' => $accessToken->getPartner(),
        ]);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (! $authorization) {
            return null;
        }

        if (! str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        return substr($authorization, 7);
    }

    private function validateToken(string $token): ?OauthAccessToken
    {
        return OauthAccessToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->with(['client.partner'])
            ->first();
    }

    private function checkRateLimit(OauthAccessToken $token, Request $request): bool
    {
        $clientId = $token->client_id;
        $endpoint = $request->path();
        $ipAddress = $request->ip();

        $windowStart = now()->startOfHour();
        $windowEnd = now()->endOfHour();

        // Check client rate limit
        $clientLimit = ApiRateLimit::where('client_id', $clientId)
            ->where('endpoint', $endpoint)
            ->where('window_start', $windowStart)
            ->first();

        if ($clientLimit && $clientLimit->requests_count >= 300) { // 300 requests per hour
            return false;
        }

        // Check IP rate limit
        $ipLimit = ApiRateLimit::where('ip_address', $ipAddress)
            ->where('endpoint', $endpoint)
            ->where('window_start', $windowStart)
            ->first();

        if ($ipLimit && $ipLimit->requests_count >= 1000) { // 1000 requests per hour per IP
            return false;
        }

        // Update counters
        $this->updateRateLimitCounter($clientId, $ipAddress, $endpoint, $windowStart, $windowEnd);

        return true;
    }

    private function updateRateLimitCounter(string $clientId, string $ipAddress, string $endpoint, $windowStart, $windowEnd): void
    {
        // Update client counter
        ApiRateLimit::updateOrCreate(
            [
                'client_id' => $clientId,
                'endpoint' => $endpoint,
                'window_start' => $windowStart,
            ],
            [
                'requests_count' => \DB::raw('requests_count + 1'),
                'window_end' => $windowEnd,
            ]
        );

        // Update IP counter
        ApiRateLimit::updateOrCreate(
            [
                'ip_address' => $ipAddress,
                'endpoint' => $endpoint,
                'window_start' => $windowStart,
            ],
            [
                'requests_count' => \DB::raw('requests_count + 1'),
                'window_end' => $windowEnd,
            ]
        );
    }

    private function logApiCall(OauthAccessToken $token, Request $request): void
    {
        $startTime = microtime(true);

        // Log the API call
        ApiAuditLog::create([
            'client_id' => $token->client_id,
            'ip_address' => $request->ip(),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'status_code' => 0, // Will be updated after response
            'response_time_ms' => 0, // Will be updated after response
            'request_size_bytes' => strlen($request->getContent()),
            'response_size_bytes' => 0, // Will be updated after response
            'user_agent' => $request->userAgent(),
            'request_headers' => $this->sanitizeHeaders($request->headers->all()),
            'response_headers' => null, // Will be updated after response
            'error_message' => null,
        ]);
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    private function unauthorizedResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'unauthorized',
            'error_description' => $message,
        ], 401);
    }

    private function forbiddenResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'insufficient_scope',
            'error_description' => $message,
        ], 403);
    }
}
