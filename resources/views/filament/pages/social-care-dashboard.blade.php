@php
    $healthStats = $this->getHealthStats();
@endphp

<x-filament::page>
    <div class="space-y-6" wire:poll.30s>
        {{-- Health Tracking KPI Cards --}}
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="bg-gradient-to-br from-pink-50 to-rose-100 rounded-xl border border-pink-200 p-5 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Активные планы</div>
                    <svg class="w-5 h-5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $healthStats['active_plans'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Планов заботы</div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200 p-5 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Клиенты</div>
                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $healthStats['clients_under_care'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Под заботой</div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-5 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Сегодня</div>
                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $healthStats['today_visits'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">
                    Визитов ({{ $healthStats['today_completed'] }} завершено)
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl border border-green-200 p-5 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Качество</div>
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $healthStats['avg_rating'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Средняя оценка</div>
            </div>
        </div>

        {{-- Week Statistics --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Статистика за неделю</span>
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Всего визитов</span>
                        <span class="text-lg font-bold text-slate-900">{{ $healthStats['week_visits'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Завершено</span>
                        <span class="text-lg font-bold text-green-600">{{ $healthStats['week_completed'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Уровень завершения</span>
                        <span class="text-lg font-bold text-blue-600">{{ $healthStats['completion_rate'] }}%</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Активные помощники</span>
                </h3>
                <div class="text-center py-4">
                    <div class="text-4xl font-black text-purple-600">{{ $healthStats['active_helpers'] }}</div>
                    <div class="text-sm text-slate-600 mt-2">Доступных помощников</div>
                </div>
            </div>
        </div>

        {{-- Widgets --}}
        @if($this->getWidgets())
            <div class="grid gap-4 md:grid-cols-3">
                @foreach($this->getWidgets() as $widget)
                    @livewire($widget, key($widget))
                @endforeach
            </div>
        @endif

        {{-- Emergency Widget --}}
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="p-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">
                    Экстренные сигналы
                </h3>
                <p class="mt-1 text-sm text-slate-600">
                    Активные экстренные события, требующие внимания
                </p>
            </div>
            <div class="p-4">
                @livewire(\App\Filament\Widgets\SocialCareEmergencyWidget::class, key('emergency-widget'))
            </div>
        </div>

        {{-- Visits Table --}}
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="p-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">
                    Визиты (сегодня + 7 дней)
                </h3>
                <p class="mt-1 text-sm text-slate-600">
                    Управление визитами и назначение помощников
                </p>
            </div>
            <div class="p-4">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament::page>

