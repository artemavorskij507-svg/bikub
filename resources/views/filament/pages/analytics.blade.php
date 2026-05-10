<x-filament::page>
    @foreach (static::getWidgets() as $widget)
        @livewire($widget)
    @endforeach
</x-filament::page>

<x-filament::page>
    <style>
        .analytics-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }
        
        .analytics-description {
            opacity: 0.95;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .widget-section {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-widget {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="analytics-container">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">📊 Панель Аналитики</h2>
                <p class="analytics-description">
                    Отслеживайте ключевые показатели эффективности вашего бизнеса в реальном времени.
                    Анализируйте заказы, выручку, метрики SLA и производительность системы.
                </p>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-80">Последнее обновление</div>
                <div class="text-lg font-semibold">{{ now()->format('H:i:s') }}</div>
                <div class="text-xs opacity-70">{{ now()->format('d.m.Y') }}</div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @if($this->getHeaderWidgets())
            <div class="widget-section stats-widget">
                <x-filament::widgets
                    :widgets="$this->getHeaderWidgets()"
                    :columns="$this->getHeaderWidgetsColumns()"
                />
            </div>
        @endif
        
        @if($this->getWidgets())
            <div class="widget-section">
                <x-filament::widgets
                    :widgets="$this->getWidgets()"
                    :columns="$this->getWidgetsColumns()"
                />
            </div>
        @endif
    </div>

    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    💡 Полезные советы
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Используйте фильтры в виджетах для анализа разных периодов времени</li>
                        <li>Экспортируйте данные для дальнейшего анализа в Excel</li>
                        <li>Обращайте внимание на метрики SLA - они критически важны для качества обслуживания</li>
                        <li>Данные обновляются автоматически каждые 30 секунд</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Listen for manual refresh
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('refresh-analytics', function() {
                    window.location.reload();
                });
            }
        });
    </script>
    @endpush
    @foreach (static::getWidgets() as $widget)
        @livewire($widget)
    @endforeach
 </x-filament::page>

