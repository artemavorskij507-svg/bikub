<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Rules\DeliveryDispatchRuleSet;
use App\Domain\Dispatch\Rules\HandymanDispatchRuleSet;
use App\Domain\Dispatch\Rules\MovingDispatchRuleSet;
use App\Domain\Dispatch\Rules\RoadsideDispatchRuleSet;
use App\Models\Operations\ServiceJob;

class ResolveDispatchRuleSetAction
{
    public function execute(ServiceJob $job): object
    {
        return match ((string) $job->service_domain) {
            'handyman' => new HandymanDispatchRuleSet(),
            'moving' => new MovingDispatchRuleSet(),
            'roadside' => new RoadsideDispatchRuleSet(),
            default => new DeliveryDispatchRuleSet(),
        };
    }
}

