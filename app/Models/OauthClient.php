<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OauthClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'name',
        'client_id',
        'client_secret',
        'scopes',
        'redirect_uris',
        'grant_type',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'scopes' => 'array',
        'redirect_uris' => 'array',
        'metadata' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(OauthAccessToken::class, 'client_id');
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'partner_id', 'partner_id');
    }

    public function apiAuditLogs(): HasMany
    {
        return $this->hasMany(ApiAuditLog::class, 'client_id');
    }

    public function rateLimits(): HasMany
    {
        return $this->hasMany(ApiRateLimit::class, 'client_id');
    }

    public static function generateCredentials(): array
    {
        return [
            'client_id' => 'glf_'.Str::random(32),
            'client_secret' => Str::random(64),
        ];
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    public function hasRedirectUri(string $uri): bool
    {
        return in_array($uri, $this->redirect_uris ?? []);
    }

    public function supportsGrantType(string $grantType): bool
    {
        return $this->grant_type === 'both' || $this->grant_type === $grantType;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function getActiveAccessTokens()
    {
        return $this->accessTokens()
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc');
    }

    public function revokeAllTokens(): void
    {
        $this->accessTokens()->update(['expires_at' => now()]);
    }
}
