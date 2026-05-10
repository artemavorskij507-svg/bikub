<?php

use App\Services\FeatureFlags\Context;
use App\Services\FeatureFlags\FeatureFlagger;

if (! function_exists('ff')) {
    function ff(string $key, ?Context $ctx = null): bool
    {
        $ctx = $ctx ?? new Context(
            orgId: auth()->user()->org_id ?? null,
            zoneId: request()->header('X-Zone-Id') ?? null,
            serviceTypeId: request()->header('X-Service-Type-Id') ?? null,
            userId: auth()->id(),
            role: optional(auth()->user())->role ?? null,
        );

        return app(FeatureFlagger::class)->enabled($key, $ctx);
    }
}

if (! function_exists('ff_rules')) {
    function ff_rules(string $key): array
    {
        return app(FeatureFlagger::class)->rules($key);
    }
}
