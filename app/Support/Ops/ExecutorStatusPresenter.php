<?php

namespace App\Support\Ops;

class ExecutorStatusPresenter
{
    public const CANONICAL = [
        'offline',
        'available',
        'busy',
        'paused',
        'suspended',
    ];

    public static function normalize(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'online', 'idle' => 'available',
            default => in_array($status, self::CANONICAL, true) ? $status : 'offline',
        };
    }

    public static function label(?string $status): string
    {
        return ucfirst(self::normalize($status));
    }

    public static function color(?string $status): string
    {
        return match (self::normalize($status)) {
            'available' => 'success',
            'busy' => 'warning',
            'paused' => 'secondary',
            'suspended' => 'danger',
            default => 'gray',
        };
    }
}

