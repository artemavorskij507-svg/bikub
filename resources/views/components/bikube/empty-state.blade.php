@props([
    'title',
    'message' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div class="bikube-os-empty">
    <h3 class="bikube-os-empty-title">{{ $title }}</h3>
    @if($message)
        <p class="bikube-os-empty-text">{{ $message }}</p>
    @endif
    @if($actionLabel && $actionHref)
        <div class="mt-3">
            <a href="{{ $actionHref }}" class="bikube-os-btn bikube-os-btn-primary">{{ $actionLabel }}</a>
        </div>
    @endif
</div>
