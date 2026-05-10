<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OidcProvider
{
    public function __construct(
        protected array $config
    ) {}

    public function authorizationUrl(string $state): string
    {
        if (empty($this->config['authorization_endpoint'])) {
            throw new RuntimeException('Authorization endpoint is not configured.');
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $this->config['scope'] ?? 'openid profile email',
            'state' => $state,
        ];

        return $this->config['authorization_endpoint'].'?'.http_build_query($params);
    }

    public function fetchUserInfo(string $code): array
    {
        $token = $this->requestToken($code);

        if (! $token) {
            throw new RuntimeException('Unable to exchange authorization code for token.');
        }

        return $this->requestUserInfo($token);
    }

    protected function requestToken(string $code): ?string
    {
        $response = Http::asForm()->post($this->config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('access_token');
    }

    protected function requestUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get($this->config['userinfo_endpoint']);

        if ($response->failed()) {
            throw new RuntimeException('Unable to fetch user info from provider.');
        }

        return $response->json() ?? [];
    }
}
