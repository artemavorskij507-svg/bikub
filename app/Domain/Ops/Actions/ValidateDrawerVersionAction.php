<?php

namespace App\Domain\Ops\Actions;

use App\Domain\Dispatch\Exceptions\StaleDrawerVersionException;
use Carbon\CarbonInterface;

class ValidateDrawerVersionAction
{
    public function execute(?string $expectedVersion, CarbonInterface|string|null $actualVersion): void
    {
        if (! $expectedVersion) {
            return;
        }

        $actual = $actualVersion instanceof CarbonInterface
            ? $actualVersion->format('Y-m-d H:i:s.u')
            : ($actualVersion ? (string) $actualVersion : null);

        if (! $actual || $actual !== $expectedVersion) {
            throw new StaleDrawerVersionException('stale_drawer_version');
        }
    }
}
