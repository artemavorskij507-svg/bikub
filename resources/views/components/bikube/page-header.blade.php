@props([
    'eyebrow' => 'BiKuBe OS',
    'title',
    'subtitle' => null,
    'badge' => null,
    'refreshUrl' => null,
    'openUrl' => null,
    'openLabel' => 'Open',
    'chips' => [],
])

<header class="bikube-os-hero">
    <div>
        <p class="bikube-os-hero-eyebrow">{{ $eyebrow }}</p>
        <h1 class="bikube-os-hero-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="bikube-os-hero-subtitle">{{ $subtitle }}</p>
        @endif

        @if(!empty($chips))
            <div class="bikube-os-status-strip">
                @foreach($chips as $chip)
                    <span class="bikube-os-chip">{{ $chip }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bikube-os-actions">
        @if($badge)
            <span class="bikube-os-badge">{{ $badge }}</span>
        @endif
        @if($refreshUrl)
            <a href="{{ $refreshUrl }}" class="bikube-os-btn bikube-os-btn-soft">Refresh</a>
        @endif
        @if($openUrl)
            <a href="{{ $openUrl }}" class="bikube-os-btn bikube-os-btn-primary">{{ $openLabel }}</a>
        @endif
        @isset($actions)
            {{ $actions }}
        @endisset
    </div>
</header>
