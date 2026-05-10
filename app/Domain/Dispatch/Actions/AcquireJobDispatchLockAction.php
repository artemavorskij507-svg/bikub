<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Exceptions\DispatchConflictException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AcquireJobDispatchLockAction
{
    public function execute(int $jobId, int $seconds = 20): array
    {
        $key = "ops:job:{$jobId}:dispatch_lock";
        $ownerToken = Str::uuid()->toString();

        $acquired = Redis::set($key, $ownerToken, 'EX', $seconds, 'NX');
        if (! $acquired) {
            throw new DispatchConflictException('job_dispatch_locked');
        }

        return [
            'key' => $key,
            'owner_token' => $ownerToken,
            'ttl_seconds' => $seconds,
        ];
    }
}
