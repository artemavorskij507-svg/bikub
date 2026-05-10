<?php

namespace App\Services\Identity;

use App\Services\Identity\Providers\MockBankIdProvider;

class IdentityVerificationService
{
    public function __construct(private readonly MockBankIdProvider $provider) {}

    public function start(array $payload = []): array
    {
        return $this->provider->start($payload);
    }

    public function complete(string $sessionId, array $payload = []): array
    {
        return $this->provider->complete($sessionId, $payload);
    }
}

