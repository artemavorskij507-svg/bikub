@props([
    'name',
    'label',
    'required' => false,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
])

<label class="block">
    <span class="mb-1 block text-sm font-semibold text-slate-700">
        {{ $label }} @if($required)<span class="text-red-600">*</span>@endif
    </span>

    @if($type === 'textarea')
        <textarea
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @class([
                'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 outline-none transition',
                'border-red-300 focus:border-red-400' => $errors->has($name),
                'border-slate-300 focus:border-blue-400' => ! $errors->has($name),
            ])
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @class([
                'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 outline-none transition',
                'border-red-300 focus:border-red-400' => $errors->has($name),
                'border-slate-300 focus:border-blue-400' => ! $errors->has($name),
            ])
        />
    @endif

    @error($name)
        <span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>
    @enderror
</label>
