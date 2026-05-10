<div class="p-4 rounded-lg border border-slate-200 bg-gradient-to-br from-white to-slate-50 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg transition-all duration-200 hover:scale-105" style="background: linear-gradient(135deg, {{ $color ?? '#3b82f6' }} 0%, {{ $color ?? '#3b82f6' }}dd 100%);">
            @if(isset($icon) && str_starts_with($icon, 'heroicon'))
                @php
                    $iconComponent = str_replace('heroicon-o-', '', $icon);
                @endphp
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    @if($iconComponent === 'truck')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    @elseif($iconComponent === 'archive-box')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    @elseif($iconComponent === 'wrench-screwdriver')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653A2.548 2.548 0 004.5 9.25v-.877c0-.414.336-.75.75-.75h3.659c.415 0 .811.157 1.103.438l4.655 5.653M11.42 15.17l-1.103-.438a2.548 2.548 0 01-1.103-.438M11.42 15.17l2.496-3.03M9.317 9.25l2.496 3.03" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    @endif
                </svg>
            @else
                <span class="text-2xl">{{ substr($name ?? 'Cat', 0, 1) }}</span>
            @endif
        </div>
        <div class="flex-1">
            <div class="font-bold text-slate-900 text-lg">{{ $name ?? 'Название категории' }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Предпросмотр категории</div>
            <div class="mt-2 flex items-center gap-2">
                <div class="w-4 h-4 rounded-full border-2 border-white shadow-sm" style="background-color: {{ $color ?? '#3b82f6' }}"></div>
                <span class="text-xs font-mono text-slate-600">{{ $color ?? '#3b82f6' }}</span>
            </div>
        </div>
    </div>
</div>

