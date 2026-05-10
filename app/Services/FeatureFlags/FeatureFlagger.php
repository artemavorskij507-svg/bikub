<?php

namespace App\Services\FeatureFlags;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagger
{
    private const CACHE_KEY = 'ff.all';

    private const CACHE_TTL = 60; // seconds

    public function enabled(string $key, Context $ctx = new Context): bool
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => FeatureFlag::with('scopes')->get()->keyBy('key')
        );

        /** @var FeatureFlag|null $flag */
        $flag = $data[$key] ?? null;
        if (! $flag || ! $flag->is_active) {
            // Fallback to config if flag not found in database
            return config("feature_flags.{$key}", false);
        }

        if ($flag->starts_at && now()->lt($flag->starts_at)) {
            return false;
        }
        if ($flag->ends_at && now()->gt($flag->ends_at)) {
            return false;
        }

        $over = function (string $scope, ?string $refId = null, ?string $refStr = null) use ($flag) {
            return $flag->scopes->firstWhere(function ($s) use ($scope, $refId, $refStr) {
                return $s->scope === $scope
                    && ($refId ? $s->ref_id === $refId : true)
                    && ($refStr ? $s->ref_str === $refStr : true);
            });
        };

        foreach ([
            ['user', $ctx->userId, null],
            ['role', null, $ctx->role],
            ['service_type', $ctx->serviceTypeId, null],
            ['zone', $ctx->zoneId, null],
            ['org', $ctx->orgId, null],
            ['global', null, null],
        ] as [$scope, $id, $str]) {
            if ($o = $over($scope, $id, $str)) {
                return (bool) $o->enabled;
            }
        }

        $bucketBase = $ctx->userId ?: $ctx->orgId ?: 'anon';
        $hash = crc32($key.':'.$bucketBase) % 100;
        if ($hash < (int) $flag->rollout_percent) {
            return true;
        }

        return (bool) $flag->default_on;
    }

    public function rules(string $key): array
    {
        $flag = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => FeatureFlag::with('scopes')->get()->keyBy('key')
        )[$key] ?? null;

        return $flag?->rules ?? [];
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
