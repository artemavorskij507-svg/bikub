@props([
    'containerClass' => '',
])

@once
    <x-bikube.os-styles />
@endonce

<section {{ $attributes->merge(['class' => 'bikube-os-root']) }}>
    <div class="bikube-os-container {{ $containerClass }}">
        {{ $slot }}
    </div>
</section>
