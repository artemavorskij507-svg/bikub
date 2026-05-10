<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    public function hosts(): array
    {
        return [
            '136\.119\.84\.22',
            '136\.119\.84\.22\.nip\.io',
            'localhost',
            '127\.0\.0\.1',
        ];
    }
}
