<?php

namespace App\Domain\Dispatch\Enums;

enum AssignmentStatus: string
{
    case PROPOSED = 'proposed';
    case OFFERED = 'offered';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REASSIGNED = 'reassigned';
}

