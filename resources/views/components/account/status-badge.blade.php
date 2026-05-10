@props([
    'label',
    'tone' => 'neutral',
])

@php
    $toneClasses = [
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-blue-100 text-blue-800',
        'neutral' => 'bg-slate-100 text-slate-700',
    ];

    $classes = $toneClasses[$tone] ?? $toneClasses['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {$classes}"]) }}>
    {{ $label }}
</span>
