<?php

namespace App\Enums;

enum PaymentFlow: string
{
    case AuthorizeCapture = 'authorize_capture';
    case DirectCharge = 'direct_charge';
}
