<?php

namespace App\Domain\Dispatch\Actions;

use App\Support\Dispatch\DispatchRuleCatalog;
use InvalidArgumentException;

class ValidateDispatchRuleValueAction
{
    public function execute(string $ruleKey, mixed $value): mixed
    {
        $meta = DispatchRuleCatalog::get($ruleKey);
        if (! $meta) {
            throw new InvalidArgumentException("Unknown dispatch rule key: {$ruleKey}");
        }

        return match ($meta['type']) {
            'float' => $this->validateFloat($ruleKey, $value, $meta),
            'int' => $this->validateInt($ruleKey, $value, $meta),
            'bool' => $this->validateBool($ruleKey, $value),
            default => throw new InvalidArgumentException("Unsupported rule type for {$ruleKey}"),
        };
    }

    private function validateFloat(string $ruleKey, mixed $value, array $meta): float
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Rule {$ruleKey} must be numeric.");
        }

        $numeric = (float) $value;
        $this->validateRange($ruleKey, $numeric, $meta);

        return $numeric;
    }

    private function validateInt(string $ruleKey, mixed $value, array $meta): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Rule {$ruleKey} must be integer.");
        }

        $numeric = (int) $value;
        $this->validateRange($ruleKey, $numeric, $meta);

        return $numeric;
    }

    private function validateBool(string $ruleKey, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        throw new InvalidArgumentException("Rule {$ruleKey} must be boolean.");
    }

    private function validateRange(string $ruleKey, float|int $value, array $meta): void
    {
        if (array_key_exists('min', $meta) && $value < $meta['min']) {
            throw new InvalidArgumentException("Rule {$ruleKey} is below minimum {$meta['min']}.");
        }
        if (array_key_exists('max', $meta) && $value > $meta['max']) {
            throw new InvalidArgumentException("Rule {$ruleKey} is above maximum {$meta['max']}.");
        }
    }
}
