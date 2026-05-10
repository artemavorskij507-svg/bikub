@props([
    'steps' => [],
    'current' => 1,
])

<ol class="grid gap-2 md:grid-cols-3">
    @foreach($steps as $index => $step)
        @php
            $position = $index + 1;
            $active = $position <= $current;
        @endphp
        <li class="rounded-xl border px-3 py-2 text-sm {{ $active ? 'border-blue-300 bg-blue-50 text-blue-900 font-semibold' : 'border-slate-200 bg-white text-slate-600' }}">
            <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full {{ $active ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $position }}</span>
            {{ $step }}
        </li>
    @endforeach
</ol>
