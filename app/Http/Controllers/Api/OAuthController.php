<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OauthAccessToken;
use App\Models\OauthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => 'required|in:client_credentials,authorization_code,refresh_token',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'scope' => 'sometimes|string',
            'redirect_uri' => 'sometimes|string',
            'code' => 'sometimes|string',
            'refresh_token' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Invalid request parameters',
                'details' => $validator->errors(),
            ], 400);
        }

        $grantType = $request->input('grant_type');

        return match ($grantType) {
            'client_credentials' => $this->handleClientCredentials($request),
            'authorization_code' => $this->handleAuthorizationCode($request),
            'refresh_token' => $this->handleRefreshToken($request),
            default => response()->json(['error' => 'unsupported_grant_type'], 400)
        };
    }

    public function authorizeRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_type' => 'required|in:code',
            'client_id' => 'required|string',
            'redirect_uri' => 'required|string',
            'scope' => 'sometimes|string',
            'state' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Invalid authorization request',
            ], 400);
        }

        $client = OauthClient::where('client_id', $request->input('client_id'))
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client credentials',
            ], 400);
        }

        if (! $client->hasRedirectUri($request->input('redirect_uri'))) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Invalid redirect URI',
            ], 400);
        }

        if (! $client->supportsGrantType('authorization_code')) {
            return response()->json([
                'error' => 'unauthorized_client',
                'error_description' => 'Client does not support authorization code flow',
            ], 400);
        }

        // Generate authorization code
        $code = 'glf_auth_'.Str::random(32);
        $scopes = $request->input('scope', 'read write');
        $state = $request->input('state');

        // Store authorization code (in production, use Redis or database)
        cache()->put("auth_code_{$code}", [
            'client_id' => $client->id,
            'user_id' => auth()->id(),
            'scopes' => explode(' ', $scopes),
            'redirect_uri' => $request->input('redirect_uri'),
            'state' => $state,
            'expires_at' => now()->addMinutes(10),
        ], 600);

        $redirectUri = $request->input('redirect_uri');
        $params = http_build_query([
            'code' => $code,
            'state' => $state,
        ]);

        return response()->json([
            'redirect_uri' => $redirectUri.'?'.$params,
        ]);
    }

    public function createClient(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|uuid|exists:partners,id',
            'name' => 'required|string|max:255',
            'scopes' => 'sometimes|array',
            'redirect_uris' => 'sometimes|array',
            'grant_type' => 'sometimes|in:client_credentials,authorization_code,both',
        ]);

        $credentials = OauthClient::generateCredentials();

        $client = OauthClient::create([
            'partner_id' => $request->input('partner_id'),
            'name' => $request->input('name'),
            'client_id' => $credentials['client_id'],
            'client_secret' => Hash::make($credentials['client_secret']),
            'scopes' => $request->input('scopes', ['read', 'write']),
            'redirect_uris' => $request->input('redirect_uris', []),
            'grant_type' => $request->input('grant_type', 'both'),
        ]);

        return response()->json([
            'client_id' => $client->client_id,
            'client_secret' => $client->client_secret,
            'name' => $client->name,
            'scopes' => $client->scopes,
            'redirect_uris' => $client->redirect_uris,
            'grant_type' => $client->grant_type,
            'created_at' => $client->created_at,
        ], 201);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = OauthAccessToken::where('token', $request->input('token'))->first();

        if ($token) {
            $token->revoke();
        }

        return response()->json(['message' => 'Token revoked successfully']);
    }

    public function introspectToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = OauthAccessToken::where('token', $request->input('token'))->first();

        if (! $token || $token->isExpired()) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'client_id' => $token->client->client_id,
            'user_id' => $token->user_id,
            'scopes' => $token->scopes,
            'expires_at' => $token->expires_at->timestamp,
            'issued_at' => $token->created_at->timestamp,
        ]);
    }

    private function handleClientCredentials(Request $request): JsonResponse
    {
        $client = OauthClient::where('client_id', $request->input('client_id'))
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($request->input('client_secret'), $client->client_secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client credentials',
            ], 401);
        }

        if (! $client->supportsGrantType('client_credentials')) {
            return response()->json([
                'error' => 'unauthorized_client',
                'error_description' => 'Client does not support client credentials flow',
            ], 400);
        }

        $scopes = $request->input('scope', 'read write');
        $scopeArray = explode(' ', $scopes);

        // Validate requested scopes
        foreach ($scopeArray as $scope) {
            if (! $client->hasScope($scope)) {
                return response()->json([
                    'error' => 'invalid_scope',
                    'error_description' => "Scope '{$scope}' is not allowed for this client",
                ], 400);
            }
        }

        // Create access token
        $token = OauthAccessToken::create([
            'client_id' => $client->id,
            'user_id' => null, // No user for client credentials
            'token' => OauthAccessToken::generateToken(),
            'scopes' => $scopeArray,
            'expires_at' => now()->addHours(24),
        ]);

        $client->updateLastUsed();

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'Bearer',
            'expires_in' => 86400, // 24 hours
            'scope' => implode(' ', $scopeArray),
        ]);
    }

    private function handleAuthorizationCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'redirect_uri' => 'required|string',
        ]);

        $codeData = cache()->get("auth_code_{$request->input('code')}");

        if (! $codeData) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid or expired authorization code',
            ], 400);
        }

        $client = OauthClient::find($codeData['client_id']);

        if (! $client || $client->client_id !== $request->input('client_id')) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client',
            ], 400);
        }

        if (! $client->hasRedirectUri($request->input('redirect_uri'))) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid redirect URI',
            ], 400);
        }

        // Create access token
        $token = OauthAccessToken::create([
            'client_id' => $client->id,
            'user_id' => $codeData['user_id'],
            'token' => OauthAccessToken::generateToken(),
            'scopes' => $codeData['scopes'],
            'expires_at' => now()->addHours(24),
        ]);

        // Clean up authorization code
        cache()->forget("auth_code_{$request->input('code')}");

        $client->updateLastUsed();

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'scope' => implode(' ', $codeData['scopes']),
        ]);
    }

    private function handleRefreshToken(Request $request): JsonResponse
    {
        // Refresh token implementation would go here
        // For now, return error as refresh tokens are not implemented
        return response()->json([
            'error' => 'unsupported_grant_type',
            'error_description' => 'Refresh token flow not implemented',
        ], 400);
    }
}
