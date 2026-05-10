<?php

namespace App\Domain\Dispatch\Rules;

class RoadsideDispatchRuleSet
{
    public function weights(): array
    {
        return [
            'proximity' => 0.20,
            'eta' => 0.30,
            'fresh_ping' => 0.10,
            'load' => 0.05,
            'shift_fit' => 0.10,
            'time_window_fit' => 0.20,
            'capacity_fit' => 0.05,
        ];
    }
}
