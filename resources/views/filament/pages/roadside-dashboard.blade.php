@php
    $stats = $this->getStats();
@endphp

<x-filament::page>
    <div class="space-y-6" wire:poll.5s>
        {{-- KPI Cards --}}
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200 p-6 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Активные</div>
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['active_emergencies'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Экстренные вызовы</div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-xl border border-amber-200 p-6 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Сегодня</div>
                    <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['today_requests'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Новых заявок</div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-6 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Партнёры</div>
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['active_partners'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Активных партнёров</div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl border border-green-200 p-6 text-slate-900 shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Помощники</div>
                    <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['active_helpers'] }}</div>
                <div class="text-sm font-medium mt-1 text-slate-600">Дорожных помощников</div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Статистика за неделю</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Заказов</span>
                        <span class="text-lg font-bold text-slate-900">{{ $stats['week_stats']['orders'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Завершено</span>
                        <span class="text-lg font-bold text-green-600">{{ $stats['week_stats']['completed'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Выручка</span>
                        <span class="text-lg font-bold text-blue-600">{{ number_format($stats['week_stats']['revenue'], 0, ',', ' ') }} kr</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Статистика за месяц</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Заказов</span>
                        <span class="text-lg font-bold text-slate-900">{{ $stats['month_stats']['orders'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Завершено</span>
                        <span class="text-lg font-bold text-green-600">{{ $stats['month_stats']['completed'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Выручка</span>
                        <span class="text-lg font-bold text-blue-600">{{ number_format($stats['month_stats']['revenue'], 0, ',', ' ') }} kr</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Быстрая навигация</h3>
            <div class="grid gap-3 md:grid-cols-3">
                <a href="{{ route('filament.resources.roadside-emergencies.index') }}" class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Экстренные вызовы</span>
                </a>
                <a href="{{ route('filament.resources.roadside-partners.index') }}" class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Партнёры-эвакуаторы</span>
                </a>
                <a href="{{ route('filament.resources.road-helper-profiles.index') }}" class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Дорожные помощники</span>
                </a>
                <a href="{{ route('filament.resources.roadside-presets.index') }}" class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Пресеты осмотра</span>
                </a>
                <a href="{{ route('filament.resources.vehicle-inspection-requests.index') }}" class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Запросы осмотра</span>
                </a>
                <div class="flex items-center space-x-3 p-3 bg-slate-50 rounded-lg">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-slate-500">Среднее время отклика: {{ $stats['avg_response_time'] }} мин</span>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
