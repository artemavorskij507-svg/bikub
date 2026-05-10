<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'token',
        'scopes',
        'expires_at',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(OauthClient::class, 'client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function revoke(): void
    {
        $this->update(['expires_at' => now()]);
    }

    public static function generateToken(): string
    {
        return 'glf_token_'.\Illuminate\Support\Str::random(64);
    }

    public function getPartner(): ?Partner
    {
        return $this->client?->partner;
    }

    public function getScopesAsString(): string
    {
        return implode(' ', $this->scopes ?? []);
    }
}
