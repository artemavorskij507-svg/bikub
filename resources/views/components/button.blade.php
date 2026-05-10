{{-- resources/views/components/button.blade.php --}}
@props([
    'link' => null,
    'variant' => 'primary',
    'type' => 'button',
    'class' => ''
])

@php
    $variantClasses = [
        'primary' => 'bg-sky-600 text-white hover:bg-sky-700',
        'secondary' => 'bg-white text-sky-600 hover:bg-sky-50 ring-1 ring-sky-600',
        'outline' => 'bg-transparent text-white border-2 border-white hover:bg-white hover:text-sky-600',
        'ghost' => 'bg-transparent text-white hover:bg-white/10',
    ];
    
    $baseClasses = 'inline-flex items-center justify-center px-6 py-3 font-semibold rounded-lg transition-colors duration-200';
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . $class;
@endphp

@if($link)
    <a href="{{ $link }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif

