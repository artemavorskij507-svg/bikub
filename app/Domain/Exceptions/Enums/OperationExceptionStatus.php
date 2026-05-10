<?php

namespace App\Domain\Exceptions\Enums;

enum OperationExceptionStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case INVESTIGATING = 'investigating';
    case MITIGATED = 'mitigated';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}

