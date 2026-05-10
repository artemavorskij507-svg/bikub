<?php

namespace App\Domain\Dispatch\Rules;

class HandymanDispatchRuleSet
{
    public function weights(): array
    {
        return [
            'proximity' => 0.20,
            'eta' => 0.20,
            'fresh_ping' => 0.05,
            'load' => 0.10,
            'shift_fit' => 0.10,
            'time_window_fit' => 0.15,
            'capacity_fit' => 0.20,
        ];
    }
}
