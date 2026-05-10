<x-filament::page>
    <div class="space-y-6" wire:poll.10s="loadData">
        <form wire:submit.prevent="loadData" class="flex items-center gap-3 mb-4">
            {{ $this->form }}
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span>Обновляется каждые 10 сек</span>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Всего ЭКО-заказов</div>
                <div class="text-3xl font-black tracking-tight text-slate-900">{{ $summary['total_orders'] ?? 0 }}</div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl border border-green-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Завершено</div>
                <div class="text-3xl font-black tracking-tight text-slate-900">{{ $summary['completed_orders'] ?? 0 }}</div>
            </div>
            <div class="bg-gradient-to-br from-sky-50 to-cyan-100 rounded-xl border border-sky-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Уровень завершения</div>
                <div class="text-3xl font-black tracking-tight text-slate-900">{{ $summary['completion_rate'] ?? 0 }}%</div>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Общий объем</div>
                <div class="text-2xl font-black tracking-tight text-slate-900">{{ number_format($summary['total_volume_m3'] ?? 0, 1, '.', ' ') }} м³</div>
            </div>
            <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-xl border border-amber-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Общий вес</div>
                <div class="text-2xl font-black tracking-tight text-slate-900">{{ number_format($summary['total_weight_kg'] ?? 0, 0, '.', ' ') }} кг</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-50 to-green-100 rounded-xl border border-emerald-200 p-5 text-slate-900 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">CO₂ сэкономлено</div>
                <div class="text-2xl font-black tracking-tight text-slate-900">{{ number_format($summary['total_co2_saved_kg'] ?? 0, 0, '.', ' ') }} кг</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Динамика заказов и CO₂</span>
                </h2>
                <div class="text-xs text-slate-500 mb-4">по дням</div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($timeSeries as $point)
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <span class="text-sm font-medium text-slate-700">{{ $point['date'] }}</span>
                            <div class="flex items-center space-x-4 text-xs text-slate-600">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">📦 {{ $point['orders_count'] }}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded">✅ {{ $point['completed_count'] }}</span>
                                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded">🌱 {{ number_format($point['co2_saved_kg'] ?? 0, 1) }} кг</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Топ партнёры</span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                        <tr class="border-b text-slate-500">
                            <th class="text-left py-1 pr-3">Партнёр</th>
                            <th class="text-right py-1 pr-3">Заказы</th>
                            <th class="text-right py-1 pr-3">Объем, м³</th>
                            <th class="text-right py-1 pr-3">Вес, кг</th>
                            <th class="text-right py-1 pr-3">CO₂, кг</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topPartners as $p)
                            <tr class="border-b">
                                <td class="py-1 pr-3">{{ $p['name'] ?? '—' }}</td>
                                <td class="py-1 pr-3 text-right">{{ $p['orders_count'] ?? 0 }}</td>
                                <td class="py-1 pr-3 text-right">{{ number_format($p['total_volume_m3'] ?? 0, 2, '.', ' ') }}</td>
                                <td class="py-1 pr-3 text-right">{{ number_format($p['total_weight_kg'] ?? 0, 2, '.', ' ') }}</td>
                                <td class="py-1 pr-3 text-right">{{ number_format($p['total_co2_saved_kg'] ?? 0, 2, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Распределение по категориям</span>
                </h2>
                <div class="space-y-2">
                    @foreach($categoryBreakdown as $row)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <span class="text-sm font-medium text-slate-700">{{ $row['category'] ?? '—' }}</span>
                            <div class="flex items-center space-x-3 text-xs">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded font-semibold">{{ $row['orders_count'] ?? 0 }} заказов</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded font-semibold">{{ $row['items_count'] ?? 0 }} предметов</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>Распределение по зонам</span>
                </h2>
                <div class="space-y-2">
                    @foreach($zoneBreakdown as $row)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <span class="text-sm font-medium text-slate-700">{{ $row['zone_code'] ?? '—' }}</span>
                            <div class="flex items-center space-x-3 text-xs">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded font-semibold">{{ $row['orders_count'] ?? 0 }} заказов</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-semibold">{{ $row['completed_count'] ?? 0 }} завершено</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament::page>


