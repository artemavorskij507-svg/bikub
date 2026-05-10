<?php

namespace App\Domain\Dispatch\Rules;

class DeliveryDispatchRuleSet
{
    public function weights(): array
    {
        return [
            'proximity' => 0.35,
            'eta' => 0.35,
            'fresh_ping' => 0.10,
            'load' => 0.05,
            'shift_fit' => 0.10,
            'time_window_fit' => 0.05,
            'capacity_fit' => 0.00,
        ];
    }
}
