<?php

namespace App\Domain\Dispatch\Actions;

class ApplyDispatchRuleOverridesAction
{
    public function execute(array $defaults, array $overrides): array
    {
        return array_replace_recursive($defaults, $overrides);
    }
}

