@props([
    'value' => null,
    'type' => 'status', // status|payment|priority
])

@php
    $normalized = strtolower((string) ($value ?? ''));
    $tone = 'neutral';

    if ($type === 'priority') {
        $tone = in_array($normalized, ['urgent', 'high', 'critical'], true) ? 'danger' : 'active';
    } elseif ($type === 'payment') {
        $tone = match ($normalized) {
            'captured', 'paid', 'paid_out' => 'success',
            'reserved' => 'active',
            'refunded' => 'violet',
            'failed' => 'danger',
            'pending' => 'pending',
            default => 'neutral',
        };
    } else {
        $tone = match ($normalized) {
            'completed', 'client_confirmed' => 'success',
            'assigned', 'worker_accepted', 'in_progress', 'arrived', 'worker_en_route' => 'active',
            'waiting_dispatch', 'pending', 'confirmed' => 'pending',
            'cancelled', 'failed', 'disputed', 'refunded' => 'danger',
            default => 'neutral',
        };
    }

    $display = $value !== null && $value !== '' ? (string) $value : '—';
@endphp

<span {{ $attributes->merge(['class' => 'bikube-os-pill bikube-os-pill-status-'.$tone]) }}>
    {{ $display }}
</span>
