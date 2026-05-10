<?php

namespace App\Domain\Operations\Enums;

enum JobKind: string
{
    case SHIPMENT = 'shipment';
    case VISIT = 'visit';
    case CREW_MOVE = 'crew_move';
    case EMERGENCY = 'emergency';
    case ERRAND = 'errand';
}

