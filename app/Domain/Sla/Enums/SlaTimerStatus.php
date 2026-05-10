<?php

namespace App\Domain\Sla\Enums;

enum SlaTimerStatus: string
{
    case PENDING = 'pending';
    case WARNING = 'warning';
    case BREACHED = 'breached';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';
}

