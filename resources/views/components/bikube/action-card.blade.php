@props([
    'title',
    'subtitle' => null,
])

<article {{ $attributes->merge(['class' => 'bikube-os-card']) }}>
    <div class="bikube-os-card-body">
        <h3 class="bikube-os-card-title">{{ $title }}</h3>
        @if($subtitle)
            <p class="bikube-os-card-subtitle">{{ $subtitle }}</p>
        @endif
        <div class="mt-3">
            {{ $slot }}
        </div>
    </div>
</article>
