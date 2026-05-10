<?php

namespace App\Support\Ops;

class DispatchModifierPresenter
{
    public static function label(string $key): string
    {
        return match ($key) {
            'time_window_risk_penalty' => 'Time window risk penalty',
            'domain_priority' => 'Domain priority modifier',
            'load_modifier' => 'Current load modifier',
            'roadside_emergency_override' => 'Roadside emergency boost',
            'runtime_override_effect' => 'Runtime override effect',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    public static function present(string $key, mixed $data): array
    {
        $modifier = (float) data_get($data, 'modifier', 0);
        $sign = $modifier > 0 ? '+' : '';

        return [
            'key' => $key,
            'label' => self::label($key),
            'value' => $modifier,
            'formatted' => $sign . (string) round($modifier, 2),
            'reason' => (string) data_get($data, 'reason', ''),
        ];
    }

    public static function presentAll(array $modifiers): array
    {
        $result = [];
        foreach ($modifiers as $key => $data) {
            $result[$key] = self::present((string) $key, $data);
        }

        return $result;
    }
}

