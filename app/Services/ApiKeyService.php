<?php

namespace App\Services;

use App\Models\ApiKey;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function generate(string $ownerType, ?int $ownerId, string $name, array $scopes = [], ?int $expiresInDays = null, bool $live = false): array
    {
        // Generate a one-time token, show only once
        $prefix = $live ? 'bk_live_' : 'bk_test_';
        $plain = $prefix.Str::random(40);
        $hash = hash('sha256', $plain);

        $apiKey = ApiKey::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'name' => $name,
            'key_hash' => $hash,
            'scopes' => $scopes,
            'expires_at' => $expiresInDays ? Carbon::now()->addDays($expiresInDays) : null,
        ]);

        return [
            'api_key' => $plain,
            'id' => $apiKey->id,
        ];
    }

    public function revoke(ApiKey $key): void
    {
        $key->update(['revoked_at' => Carbon::now()]);
    }

    public function rotate(ApiKey $oldKey, ?array $newScopes = null): array
    {
        // Revoke the old key
        $this->revoke($oldKey);

        // Create new key with same owner and optional new scopes
        $scopes = $newScopes ?? $oldKey->scopes ?? [];
        $result = $this->generate(
            $oldKey->owner_type,
            $oldKey->owner_id,
            $oldKey->name.' (rotated)',
            $scopes,
            $oldKey->expires_at ? $oldKey->expires_at->diffInDays(Carbon::now()) : null,
            str_starts_with($oldKey->key_hash, 'bk_live') // Detect if originally live key (rough heuristic; better to store flag)
        );

        // Log rotation event (independent of the revoke and create logs)
        try {
            app(\App\Services\AuditLogger::class)->log(
                'api_key_rotated',
                ApiKey::class,
                $oldKey->id,
                [
                    'old_key_id' => $oldKey->id,
                    'name' => $oldKey->name,
                    'old_scopes' => $oldKey->scopes,
                ],
                [
                    'new_key_id' => $result['id'],
                    'name' => $oldKey->name.' (rotated)',
                    'new_scopes' => $scopes,
                ],
                request()
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to log api_key_rotated', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    public function validateKey(string $provided): ?ApiKey
    {
        $hash = hash('sha256', $provided);

        return ApiKey::where('key_hash', $hash)->whereNull('revoked_at')->first();
    }

    /**
     * Check if a key is active (not revoked and not expired).
     */
    public function isActive(ApiKey $key): bool
    {
        if ($key->revoked_at) {
            return false;
        }
        if ($key->expires_at && $key->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get the status of a key: 'active', 'expired', or 'revoked'.
     */
    public function getStatus(ApiKey $key): string
    {
        if ($key->revoked_at) {
            return 'revoked';
        }
        if ($key->expires_at && $key->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
