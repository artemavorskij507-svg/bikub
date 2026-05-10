<?php

namespace App\Domain\Dispatch\Actions;

class NormalizeDispatchRuleKeyAction
{
    public function execute(string $ruleKey): string
    {
        $normalized = trim(strtolower($ruleKey));
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        $normalized = str_replace(['..', '__'], ['.', '_'], $normalized);

        return trim($normalized, '.');
    }
}
