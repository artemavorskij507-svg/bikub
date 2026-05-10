<?php

namespace App\Domain\Operations\Enums;

enum ServiceDomain: string
{
    case DELIVERY = 'delivery';
    case HANDYMAN = 'handyman';
    case MOVING = 'moving';
    case ROADSIDE = 'roadside';
    case SOCIAL_CARE = 'social_care';
}

