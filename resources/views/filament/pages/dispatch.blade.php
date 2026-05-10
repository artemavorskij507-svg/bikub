<x-filament::page>
    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">Фильтры</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Модуль</label>
                    <select wire:model.live="filters.module" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Все модули</option>
                        <option value="care">Care</option>
                        <option value="eco">Eco</option>
                        <option value="market">Market</option>
                        <option value="tow">Tow</option>
                        <option value="rent">Rent</option>
                        <option value="shuttle">Shuttle</option>
                        <option value="master">Master</option>
                        <option value="food">Food</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Зона</label>
                    <select wire:model.live="filters.zone" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Все зоны</option>
                        <option value="north">Север</option>
                        <option value="center">Центр</option>
                        <option value="south">Юг</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Слот</label>
                    <select wire:model.live="filters.slot" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Все слоты</option>
                        <option value="morning">Утро</option>
                        <option value="day">День</option>
                        <option value="evening">Вечер</option>
                        <option value="weekend_morning">Выходные утро</option>
                        <option value="weekend_afternoon">Выходные день</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                    <select wire:model.live="filters.status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Все статусы</option>
                        <option value="pending">Ожидает</option>
                        <option value="confirmed">Подтвержден</option>
                        <option value="in_progress">В работе</option>
                        <option value="completed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <button wire:click="updateFilters" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Применить фильтры
                </button>
            </div>
        </div>

        <!-- Error Message -->
        @if(empty($dispatchState))
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Не удалось загрузить данные диспетчерской</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Проверьте, что API маршруты доступны и сервер работает корректно.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Summary Cards -->
        @if(!empty($dispatchState['summary']))
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $dispatchState['summary']['total_orders'] }}</div>
                <div class="text-sm text-gray-600">Всего заказов</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-yellow-600">{{ $dispatchState['summary']['pending_orders'] }}</div>
                <div class="text-sm text-gray-600">Ожидают</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $dispatchState['summary']['in_progress_orders'] }}</div>
                <div class="text-sm text-gray-600">В работе</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-red-600">{{ $dispatchState['summary']['sla_at_risk'] }}</div>
                <div class="text-sm text-gray-600">SLA под угрозой</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-orange-600">{{ $dispatchState['summary']['overbooked_slots'] }}</div>
                <div class="text-sm text-gray-600">Переполненные слоты</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-green-600">{{ $dispatchState['summary']['active_couriers'] }}</div>
                <div class="text-sm text-gray-600">Активные курьеры</div>
            </div>
        </div>
        @endif

        <!-- Schedule Slots Status -->
        @if(!empty($dispatchState['slots']))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">Статус временных слотов</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($dispatchState['slots'] as $slot)
                <div class="border rounded-lg p-4 {{ $slot['is_overbooked'] ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-medium">{{ $slot['name'] }}</h4>
                        <span class="text-sm text-gray-600">{{ $slot['from'] }} - {{ $slot['to'] }}</span>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Забронировано:</span>
                            <span class="font-medium">{{ $slot['booked'] }}/{{ $slot['capacity'] }}</span>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $percentage = $slot['capacity'] > 0 ? ($slot['booked'] / $slot['capacity']) * 100 : 0;
                            @endphp
                            <div class="bg-{{ $slot['is_overbooked'] ? 'red' : 'blue' }}-600 h-2 rounded-full" 
                                 style="width: {{ min(100, $percentage) }}%"></div>
                        </div>
                        
                        @if($slot['is_overbooked'])
                        <div class="text-sm text-red-600 font-medium">
                            Переполнение: +{{ number_format($slot['overbooking_percentage'], 1) }}%
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Orders List -->
        @if(!empty($dispatchState['orders']))
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-medium">Заказы</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Заказ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Курьер</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($dispatchState['orders'] as $order)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $order['order_number'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $order['scheduled_at'] }}</div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $order['customer']['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $order['customer']['phone'] }}</div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $order['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $order['status'] === 'in_progress' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $order['status'] === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($order['status']) }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $order['sla_risk'] === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $order['sla_risk'] === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ $order['sla_risk'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $order['sla_risk'] === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                    {{ ucfirst($order['sla_risk']) }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(!empty($order['tasks']))
                                    @foreach($order['tasks'] as $task)
                                        @if($task['assignee'])
                                        <div class="text-sm text-gray-900">{{ $task['assignee']['name'] }}</div>
                                        @else
                                        <div class="text-sm text-gray-500">Не назначен</div>
                                        @endif
                                    @endforeach
                                @else
                                <div class="text-sm text-gray-500">Нет задач</div>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if($order['status'] === 'pending')
                                    <button wire:click="updateOrderStatus('{{ $order['id'] }}', 'confirmed')" 
                                            class="text-blue-600 hover:text-blue-900">Подтвердить</button>
                                    @endif
                                    
                                    @if($order['status'] === 'confirmed')
                                    <button wire:click="updateOrderStatus('{{ $order['id'] }}', 'in_progress')" 
                                            class="text-green-600 hover:text-green-900">Начать</button>
                                    @endif
                                    
                                    @if($order['status'] === 'in_progress')
                                    <button wire:click="updateOrderStatus('{{ $order['id'] }}', 'completed')" 
                                            class="text-gray-600 hover:text-gray-900">Завершить</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Winter Protocol Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium">Зимний протокол</h3>
                    <p class="text-sm text-gray-600">
                        Статус: 
                        <span class="font-medium {{ $winterProtocolEnabled ? 'text-orange-600' : 'text-gray-600' }}">
                            {{ $winterProtocolEnabled ? 'Активен' : 'Неактивен' }}
                        </span>
                    </p>
                    @if($winterProtocolEnabled)
                    <p class="text-sm text-gray-600">
                        Коэффициент ETA: {{ $etaMultiplier }}x | Повышение приоритета: +{{ $priorityBoost }}
                    </p>
                    @endif
                </div>
                
                <button wire:click="toggleWinterProtocol" 
                        class="px-4 py-2 rounded-md {{ $winterProtocolEnabled ? 'bg-orange-600 hover:bg-orange-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white">
                    {{ $winterProtocolEnabled ? 'Отключить' : 'Включить' }}
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        document.addEventListener('livewire:load', () => {
            const componentId = @js($this->id ?? null);
            const component = componentId ? Livewire.find(componentId) : null;

            if (!component) {
                return;
            }

            const refresh = () => component.call('loadDispatchState');
            // Первичный рефреш (на случай, если страница открыта долго)
            refresh();

            const intervalId = setInterval(refresh, 30000);

            const cleanup = () => clearInterval(intervalId);
            document.addEventListener('turbo:before-render', cleanup, { once: true });
            document.addEventListener('livewire:navigated', cleanup, { once: true });
        });
    </script>
    @endscript
</x-filament::page>
