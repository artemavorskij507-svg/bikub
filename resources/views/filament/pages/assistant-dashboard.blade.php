<x-filament::page>
    <div class="assistant-dashboard space-y-6 bg-gradient-to-br from-gray-50 via-white to-gray-50 min-h-screen -m-6 p-6">
        {{-- Hero Header --}}
        <div class="bg-white rounded-3xl shadow-xl p-8 text-gray-900 relative overflow-hidden transform transition-all duration-300 hover:shadow-2xl border-2 border-gray-200">
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-100/50 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-purple-100/50 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg border-2 border-blue-400">
                                <span class="text-4xl animate-bounce">⚡</span>
                            </div>
                            <div>
                                <h1 class="text-5xl font-black mb-2 text-gray-900">
                                    Bikube Smart Assistant
                                </h1>
                                <p class="text-gray-700 text-lg font-semibold">Интеллектуальная система поддержки курьеров в реальном времени</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 mt-6">
                            <div class="flex items-center gap-2 bg-green-50 border-2 border-green-200 px-5 py-2.5 rounded-xl shadow-sm hover:bg-green-100 transition-colors">
                                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse shadow-lg"></span>
                                <span class="font-bold text-gray-900">Система активна</span>
                            </div>
                            <div class="flex items-center gap-2 bg-blue-50 border-2 border-blue-200 px-5 py-2.5 rounded-xl shadow-sm hover:bg-blue-100 transition-colors">
                                <svg class="w-5 h-5 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span class="font-bold text-gray-900">{{ $stats['couriers_online'] ?? 0 }} курьеров онлайн</span>
                            </div>
                            <div class="flex items-center gap-2 bg-purple-50 border-2 border-purple-200 px-5 py-2.5 rounded-xl shadow-sm hover:bg-purple-100 transition-colors">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span class="font-bold text-gray-900">{{ $stats['active_orders'] ?? 0 }} активных заказов</span>
                            </div>
                        </div>
                    </div>
                    <x-filament::button 
                        wire:click="broadcastInsights"
                        :disabled="$isBroadcasting"
                        color="success"
                        size="lg"
                        icon="heroicon-o-paper-airplane"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 border-2 border-green-500 shadow-xl hover:shadow-2xl hover:shadow-green-500/50 hover:scale-105 transition-all duration-300 text-white font-bold px-8 py-6 text-lg"
                    >
                        <span wire:loading.remove wire:target="broadcastInsights" class="flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Отправить подсказки
                        </span>
                        <span wire:loading wire:target="broadcastInsights" class="flex items-center gap-2">
                            <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg>
                            Отправка...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Statistics Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 bg-white p-6 rounded-3xl border-2 border-gray-200 shadow-xl">
            {{-- Active Orders --}}
            <div class="group bg-white rounded-2xl shadow-lg p-6 border-2 border-blue-200 hover:border-blue-400 hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center shadow-md">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-black text-blue-700 bg-blue-100 px-3 py-1.5 rounded-full border-2 border-blue-300">📊 ACTIVE</span>
                    </div>
                    <p class="text-sm text-gray-700 font-bold mb-2">Активных заказов</p>
                    <p class="text-4xl font-black text-gray-900 mb-4">{{ $stats['active_orders'] ?? 0 }}</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-blue-600 h-full rounded-full transition-all duration-500 shadow-md" style="width: {{ min(($stats['active_orders'] ?? 0) * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Couriers Online (Live) --}}
            <div class="group bg-white rounded-2xl shadow-lg p-6 border-2 border-green-200 hover:border-green-400 hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-green-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="absolute top-3 right-3 flex items-center gap-2 bg-green-100 px-3 py-1 rounded-full border-2 border-green-300">
                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse shadow-lg"></span>
                    <span class="text-xs font-black text-green-700">LIVE</span>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center shadow-md">
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-black text-green-700 bg-green-100 px-3 py-1.5 rounded-full border-2 border-green-300">🟢 ONLINE</span>
                    </div>
                    <p class="text-sm text-gray-700 font-bold mb-2">Курьеров на линии</p>
                    <p class="text-4xl font-black text-gray-900 mb-4">
                        <span wire:loading.remove wire:target="refreshStats">{{ $stats['couriers_online'] ?? 0 }}</span>
                        <span wire:loading wire:target="refreshStats" class="inline-flex items-center">
                            <svg class="animate-spin h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-green-600 h-full rounded-full transition-all duration-500 shadow-md" style="width: {{ min(($stats['couriers_online'] ?? 0) * 5, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Insights Sent --}}
            <div class="group bg-white rounded-2xl shadow-lg p-6 border-2 border-amber-200 hover:border-amber-400 hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-amber-100 flex items-center justify-center shadow-md">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-black text-amber-700 bg-amber-100 px-3 py-1.5 rounded-full border-2 border-amber-300">💡 TIPS</span>
                    </div>
                    <p class="text-sm text-gray-700 font-bold mb-2">Подсказок отправлено</p>
                    <p class="text-4xl font-black text-gray-900 mb-4">{{ $stats['total_insights_sent'] ?? 0 }}</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-amber-600 h-full rounded-full transition-all duration-500 shadow-md" style="width: 100%"></div>
                    </div>
                </div>
            </div>

            {{-- System Status --}}
            <div class="group bg-white rounded-2xl shadow-lg p-6 border-2 border-purple-200 hover:border-purple-400 hover:shadow-2xl transition-all duration-300 hover:scale-105 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center shadow-md">
                            <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-black text-purple-700 bg-purple-100 px-3 py-1.5 rounded-full border-2 border-purple-300">⚙️ SYSTEM</span>
                    </div>
                    <p class="text-sm text-gray-700 font-bold mb-2">Статус системы</p>
                    <p class="text-4xl font-black text-gray-900 mb-4">OK</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-purple-600 h-full rounded-full transition-all duration-500 shadow-md" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Active Orders List --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl shadow-xl border-2 border-gray-200 overflow-hidden">
                    <div class="p-6 border-b-2 border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Активные заказы
                        </h3>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg text-sm font-bold border-2 border-blue-300">
                            {{ $stats['active_orders'] ?? 0 }} в работе
                        </span>
                    </div>
                    <div class="p-6 bg-white" x-data="{ loaded: 5 }" x-init="$nextTick(() => { const observer = new IntersectionObserver(entries => { entries.forEach(e => { if (e.isIntersecting) { loaded += 5; } }); }); observer.observe($refs.bottomSentinel); })">
                        @forelse(($activeOrders ?? []) as $idx => $item)
                            <template x-if="loaded > {{ $idx }}">
                            @php
                                $order = $item['order'] ?? null;
                                $insights = $item['insights'] ?? [];
                                
                                // Преобразуем объект в массив, если необходимо
                                if ($order && is_object($order)) {
                                    $order = (array) $order;
                                }
                                
                                // Получаем assigned_user безопасно
                                $assignedUser = null;
                                if ($order && is_array($order)) {
                                    $assignedUser = $order['assigned_user'] ?? null;
                                    if ($assignedUser && is_object($assignedUser)) {
                                        $assignedUser = (array) $assignedUser;
                                    }
                                }
                            @endphp
                            @if($order && is_array($order))
                            <div class="bg-gray-50 rounded-2xl p-4 mb-3 border-l-4 border-blue-500 hover:bg-gray-100 transition-all duration-300 group border-2 border-gray-200">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center font-black text-white text-lg shadow-lg group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                                        {{ substr($order['order_number'] ?? 'N/A', -2) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                                🔖 #{{ $order['order_number'] ?? 'N/A' }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold border
                                                @if(($order['status'] ?? '') === 'delivered') bg-green-100 text-green-800 border-green-300
                                                @elseif(($order['status'] ?? '') === 'in_progress') bg-blue-100 text-blue-800 border-blue-300
                                                @elseif(($order['status'] ?? '') === 'pending') bg-yellow-100 text-yellow-800 border-yellow-300
                                                @else bg-gray-100 text-gray-800 border-gray-300
                                                @endif">
                                                {{ strtoupper($order['status'] ?? 'UNKNOWN') }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-900 truncate font-bold">
                                            👤 {{ $assignedUser['name'] ?? 'Не назначен' }}
                                        </p>
                                        @if($insights && isset($insights['suggestions']))
                                        <p class="text-xs text-gray-700 mt-1 truncate">
                                            💡 {{ $insights['suggestions'][0] ?? 'Оптимизация маршрута...' }}
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            </template>
                        @empty
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-gray-300">
                                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <p class="text-gray-700 font-medium">Нет активных заказов в данный момент</p>
                            </div>
                        @endforelse
                        <div x-ref="bottomSentinel"></div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Assistant Status --}}
            <div class="space-y-6">
                <div class="bg-white rounded-3xl shadow-xl border-2 border-gray-200 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100/30 rounded-full -mr-16 -mt-16 blur-xl"></div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2 relative z-10">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        Как это работает?
                    </h3>
                    <div class="space-y-6 relative z-10">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center border-2 border-blue-300 flex-shrink-0">
                                <span class="text-blue-700 font-bold">1</span>
                            </div>
                            <div>
                                <h4 class="text-gray-900 font-bold mb-1">Сбор данных</h4>
                                <p class="text-gray-700 text-sm">Система анализирует активные заказы и локации курьеров</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center border-2 border-purple-300 flex-shrink-0">
                                <span class="text-purple-700 font-bold">2</span>
                            </div>
                            <div>
                                <h4 class="text-gray-900 font-bold mb-1">Генерация подсказок</h4>
                                <p class="text-gray-700 text-sm">AI формирует оптимальные маршруты и советы</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center border-2 border-green-300 flex-shrink-0">
                                <span class="text-green-700 font-bold">3</span>
                            </div>
                            <div>
                                <h4 class="text-gray-900 font-bold mb-1">Отправка</h4>
                                <p class="text-gray-700 text-sm">Курьеры получают уведомления в приложении</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --text-primary: #1F2937; /* text-gray-800 */
            --text-secondary: #374151; /* text-gray-700 */
            --text-muted: #6B7280; /* text-gray-500 */
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .animate-shimmer {
            animation: shimmer 3s infinite linear;
        }
        
        /* Улучшение контрастности для всех текстовых элементов */
        .assistant-dashboard {
            color: var(--text-primary);
        }
        
        .assistant-dashboard .text-muted {
            color: var(--text-muted) !important;
        }
        
        .assistant-dashboard .text-secondary {
            color: var(--text-secondary) !important;
        }
    </style>
</x-filament::page>
