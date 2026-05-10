@props([
    'candidate' => [],
])

@php($modifiers = (array) data_get($candidate, 'modifiers', []))

<div class="mt-2">
    <div class="text-xs font-semibold text-gray-600">Applied modifiers</div>
    @if(empty($modifiers))
        <div class="text-xs text-gray-500">No modifiers.</div>
    @else
        <div class="mt-1 space-y-1 text-xs">
            @foreach($modifiers as $modifier)
                <div>
                    {{ data_get($modifier, 'label', '-') }}:
                    <span class="{{ (float) data_get($modifier, 'value', 0) >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ data_get($modifier, 'formatted', '0') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>

