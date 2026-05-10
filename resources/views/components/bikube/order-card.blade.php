@props([
    'title',
    'meta' => null,
    'status' => null,
    'payment' => null,
    'priority' => null,
    'accent' => 'active',
])

<article {{ $attributes->merge(['class' => 'bikube-os-card bikube-os-order-card']) }}>
    <div class="bikube-os-card-body">
        <div class="bikube-os-order-head">
            <div>
                <h3 class="bikube-os-order-title">{{ $title }}</h3>
                @if($meta)
                    <p class="bikube-os-order-meta">{{ $meta }}</p>
                @endif
            </div>
            <div class="bikube-os-badges">
                @if($status !== null)
                    <x-bikube.status-badge :value="$status" type="status" />
                @endif
                @if($payment !== null)
                    <x-bikube.status-badge :value="$payment" type="payment" />
                @endif
                @if($priority !== null)
                    <x-bikube.status-badge :value="$priority" type="priority" />
                @endif
            </div>
        </div>

        <div class="mt-3">
            {{ $slot }}
        </div>
    </div>
</article>
