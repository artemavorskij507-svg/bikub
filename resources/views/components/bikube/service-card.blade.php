@props([
    'title',
    'description' => null,
    'price' => null,
    'sla' => null,
    'ctaLabel' => 'Open',
    'ctaHref' => '#',
])

<x-bikube.action-card :title="$title" {{ $attributes }}>
    @if($description)
        <p class="text-sm text-slate-600">{{ $description }}</p>
    @endif

    <div class="mt-3 grid gap-2 text-sm text-slate-700">
        @if($price)
            <div><span class="font-semibold text-slate-500">Price:</span> {{ $price }}</div>
        @endif
        @if($sla)
            <div><span class="font-semibold text-slate-500">SLA:</span> {{ $sla }}</div>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ $ctaHref }}" class="bikube-os-btn bikube-os-btn-primary">{{ $ctaLabel }}</a>
    </div>
</x-bikube.action-card>
