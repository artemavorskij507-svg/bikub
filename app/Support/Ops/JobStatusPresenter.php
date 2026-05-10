<?php

namespace App\Support\Ops;

class JobStatusPresenter
{
    public const CANONICAL = [
        'pending_dispatch',
        'assigned',
        'en_route',
        'arrived',
        'in_progress',
        'completed',
        'cancelled',
        'failed',
    ];

    public static function normalize(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'pending' => 'pending_dispatch',
            'started' => 'in_progress',
            default => in_array($status, self::CANONICAL, true) ? $status : 'pending_dispatch',
        };
    }

    public static function label(?string $status): string
    {
        return match (self::normalize($status)) {
            'pending_dispatch' => 'Pending',
            'assigned' => 'Assigned',
            'en_route' => 'En route',
            'arrived' => 'Arrived',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
            default => 'Pending',
        };
    }

    public static function color(?string $status): string
    {
        return match (self::normalize($status)) {
            'pending_dispatch' => 'secondary',
            'assigned', 'en_route', 'arrived', 'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled', 'failed' => 'danger',
            default => 'secondary',
        };
    }
}

