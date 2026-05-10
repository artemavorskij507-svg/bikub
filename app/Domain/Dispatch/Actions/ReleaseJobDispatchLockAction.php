<?php

namespace App\Domain\Dispatch\Actions;

use Illuminate\Support\Facades\Redis;

class ReleaseJobDispatchLockAction
{
    public function execute(?string $key, ?string $ownerToken): void
    {
        if (! $key || ! $ownerToken) {
            return;
        }

        Redis::eval(
            <<<'LUA'
if redis.call("get", KEYS[1]) == ARGV[1] then
  return redis.call("del", KEYS[1])
else
  return 0
end
LUA,
            1,
            $key,
            $ownerToken
        );
    }
}
