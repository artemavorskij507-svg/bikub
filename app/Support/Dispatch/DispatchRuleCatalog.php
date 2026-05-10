<?php

namespace App\Support\Dispatch;

class DispatchRuleCatalog
{
    public static function all(): array
    {
        return [
            'weights.proximity' => ['type' => 'float', 'min' => 0, 'max' => 5, 'label' => 'Proximity Weight'],
            'weights.eta' => ['type' => 'float', 'min' => 0, 'max' => 5, 'label' => 'ETA Weight'],
            'weights.time_window_fit' => ['type' => 'float', 'min' => 0, 'max' => 5, 'label' => 'Time Window Weight'],
            'weights.capacity_fit' => ['type' => 'float', 'min' => 0, 'max' => 5, 'label' => 'Capacity Fit Weight'],
            'weights.shift_fit' => ['type' => 'float', 'min' => 0, 'max' => 5, 'label' => 'Shift Fit Weight'],
            'modifiers.window_high_risk_penalty' => ['type' => 'int', 'min' => -100, 'max' => 0, 'label' => 'High Risk Window Penalty'],
            'modifiers.window_medium_risk_penalty' => ['type' => 'int', 'min' => -100, 'max' => 0, 'label' => 'Medium Risk Window Penalty'],
            'modifiers.emergency_boost' => ['type' => 'int', 'min' => 0, 'max' => 100, 'label' => 'Emergency Boost'],
            'modifiers.domain_priority_boost' => ['type' => 'int', 'min' => -100, 'max' => 100, 'label' => 'Domain Priority Boost'],
            'modifiers.load_penalty_scale' => ['type' => 'float', 'min' => 0, 'max' => 10, 'label' => 'Load Penalty Scale'],
            'roadside.acceptance_timeout_seconds' => ['type' => 'int', 'min' => 30, 'max' => 1800, 'label' => 'Roadside Acceptance Timeout (s)'],
            'roadside.preemption_enabled' => ['type' => 'bool', 'label' => 'Roadside Preemption Enabled'],
            'moving.default_required_team_size' => ['type' => 'int', 'min' => 2, 'max' => 6, 'label' => 'Moving Team Size (Default)'],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $key => $meta) {
            $options[$key] = $meta['label'] . " ({$key})";
        }

        return $options;
    }

    public static function defaultRulesByDomain(string $serviceDomain): array
    {
        $weights = match ($serviceDomain) {
            'handyman' => [
                'proximity' => 0.20,
                'eta' => 0.20,
                'fresh_ping' => 0.05,
                'load' => 0.10,
                'shift_fit' => 0.10,
                'time_window_fit' => 0.15,
                'capacity_fit' => 0.20,
            ],
            'moving' => [
                'proximity' => 0.15,
                'eta' => 0.20,
                'fresh_ping' => 0.05,
                'load' => 0.10,
                'shift_fit' => 0.15,
                'time_window_fit' => 0.20,
                'capacity_fit' => 0.15,
            ],
            'roadside' => [
                'proximity' => 0.20,
                'eta' => 0.30,
                'fresh_ping' => 0.10,
                'load' => 0.05,
                'shift_fit' => 0.10,
                'time_window_fit' => 0.20,
                'capacity_fit' => 0.05,
            ],
            default => [
                'proximity' => 0.35,
                'eta' => 0.35,
                'fresh_ping' => 0.10,
                'load' => 0.05,
                'shift_fit' => 0.10,
                'time_window_fit' => 0.05,
                'capacity_fit' => 0.00,
            ],
        };

        return [
            'weights' => $weights,
            'modifiers' => [
                'window_high_risk_penalty' => -12,
                'window_medium_risk_penalty' => -5,
                'emergency_boost' => 20,
                'domain_priority_boost' => 0,
                'load_penalty_scale' => 1.0,
                'idle_executor_boost' => 4,
                'load_1_penalty' => 0,
                'load_2_penalty' => -6,
                'load_3_plus_penalty' => -12,
            ],
            'roadside' => [
                'preemption_enabled' => true,
                'acceptance_timeout_seconds' => 120,
            ],
            'moving' => [
                'default_required_team_size' => 2,
            ],
        ];
    }

    public static function defaultValueFor(string $serviceDomain, string $ruleKey): mixed
    {
        return data_get(self::defaultRulesByDomain($serviceDomain), $ruleKey);
    }

    public static function impactLevel(string $serviceDomain, string $ruleKey, mixed $overrideValue): string
    {
        $default = self::defaultValueFor($serviceDomain, $ruleKey);
        $deltaPercent = self::deltaPercent($default, $overrideValue);

        if ($deltaPercent === null) {
            if (is_bool($default) && $default !== (bool) $overrideValue) {
                return 'high_impact';
            }

            return 'normal';
        }

        return match (true) {
            $deltaPercent > 50 => 'high_impact',
            $deltaPercent > 25 => 'aggressive_override',
            default => 'normal',
        };
    }

    public static function deltaPercent(mixed $default, mixed $override): ?float
    {
        if (! is_numeric($default) || ! is_numeric($override)) {
            return null;
        }

        $defaultFloat = (float) $default;
        $overrideFloat = (float) $override;
        if (abs($defaultFloat) < 0.000001) {
            return null;
        }

        return round((abs($overrideFloat - $defaultFloat) / abs($defaultFloat)) * 100, 2);
    }
}
