<?php

namespace App\Domain\Operations\Enums;

enum ServiceJobStatus: string
{
    case DRAFT = 'draft';
    case PENDING_DISPATCH = 'pending_dispatch';
    case DISPATCHING = 'dispatching';
    case ASSIGNED = 'assigned';
    case EN_ROUTE = 'en_route';
    case ARRIVED = 'arrived';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}

