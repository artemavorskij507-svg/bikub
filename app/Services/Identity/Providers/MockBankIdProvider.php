<?php

namespace App\Services\Identity\Providers;

class MockBankIdProvider
{
    public function start(array $payload = []): array
    {
        return ['session_id' => 'mock-'.uniqid(), 'status' => 'started'];
    }

    public function complete(string $sessionId, array $payload = []): array
    {
        return [
            'session_id' => $sessionId,
            'status' => 'verified',
            'full_name' => $payload['full_name'] ?? 'Mock User',
            'date_of_birth' => $payload['date_of_birth'] ?? null,
            'national_identity_hash' => hash('sha256', (string) ($payload['national_identity'] ?? uniqid())),
        ];
    }
}

