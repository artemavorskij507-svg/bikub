@props([
    'label',
    'value' => 0,
    'hint' => null,
    'tone' => 'slate',
])

<article class="bikube-os-card bikube-os-kpi bikube-os-kpi-tone-{{ $tone }}">
    <div class="bikube-os-card-body">
        <h3 class="bikube-os-card-title">{{ $label }}</h3>
        <p class="bikube-os-kpi-value">{{ $value }}</p>
        @if($hint)
            <p class="bikube-os-kpi-hint">{{ $hint }}</p>
        @endif
    </div>
</article>
