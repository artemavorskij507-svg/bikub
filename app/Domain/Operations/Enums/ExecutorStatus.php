<?php

namespace App\Domain\Operations\Enums;

enum ExecutorStatus: string
{
    case OFFLINE = 'offline';
    case AVAILABLE = 'available';
    case BUSY = 'busy';
    case PAUSED = 'paused';
    case SUSPENDED = 'suspended';
}

