@props([
    'runtime' => [],
])

@php($rules = (array) data_get($runtime, 'effective_rule_values', []))

<div class="space-y-1">
    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Effective Runtime Rules</div>
    @if(empty($rules))
        <div class="text-xs text-gray-500">No runtime rule overrides.</div>
    @else
        <div class="grid grid-cols-1 gap-1 text-xs text-gray-700">
            <div>ETA weight: {{ data_get($rules, 'weights.eta', 'n/a') }}</div>
            <div>Time window weight: {{ data_get($rules, 'weights.time_window_fit', 'n/a') }}</div>
            <div>Capacity weight: {{ data_get($rules, 'weights.capacity_fit', 'n/a') }}</div>
            <div>Emergency boost: {{ data_get($rules, 'modifiers.emergency_boost', 'n/a') }}</div>
            <div>High-risk penalty: {{ data_get($rules, 'modifiers.window_high_risk_penalty', 'n/a') }}</div>
            <div>Acceptance timeout: {{ data_get($rules, 'roadside.acceptance_timeout_seconds', 'n/a') }} sec</div>
        </div>
    @endif
</div>

