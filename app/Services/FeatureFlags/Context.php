<?php

namespace App\Services\FeatureFlags;

class Context
{
    public function __construct(
        public ?string $orgId = null,
        public ?string $zoneId = null,
        public ?string $serviceTypeId = null,
        public ?string $userId = null,
        public ?string $role = null,
    ) {}
}
