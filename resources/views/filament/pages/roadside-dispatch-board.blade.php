<x-filament::page>
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        {{-- New Jobs --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Новые заявки</div>
            <div class="text-2xl font-extrabold text-gray-900">{{ $this->stats['new_jobs'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Созданы за последние 24 часа</div>
        </div>

        {{-- Active Jobs --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Активные</div>
            <div class="text-2xl font-extrabold text-gray-900">{{ $this->stats['active_jobs'] }}</div>
            <div class="text-xs text-gray-500 mt-1">В работе: pending / assigned / in&nbsp;progress</div>
        </div>

        {{-- Awaiting Assignment --}}
        <div class="bg-white rounded-lg shadow-sm border border-yellow-200 p-4 {{ $this->stats['awaiting_assign'] > 0 ? 'bg-yellow-50' : '' }}">
            <div class="text-sm text-gray-600 mb-1">Ждут назначения</div>
            <div class="text-2xl font-extrabold {{ $this->stats['awaiting_assign'] > 0 ? 'text-yellow-700' : 'text-gray-900' }}">
                {{ $this->stats['awaiting_assign'] }}
            </div>
            <div class="text-xs text-gray-500 mt-1">Нет назначенного помощника/партнёра</div>
        </div>

        {{-- Overdue Assignment --}}
        <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4 {{ $this->stats['overdue_assign'] > 0 ? 'bg-red-50' : '' }}">
            <div class="text-sm text-gray-600 mb-1">Просрочено по назначению</div>
            <div class="text-2xl font-extrabold {{ $this->stats['overdue_assign'] > 0 ? 'text-red-700' : 'text-gray-900' }}">
                {{ $this->stats['overdue_assign'] }}
            </div>
            <div class="text-xs text-gray-500 mt-1">Превышен SLA по времени назначения</div>
        </div>

        {{-- Overdue Arrival --}}
        <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4 {{ $this->stats['overdue_arrival'] > 0 ? 'bg-red-50' : '' }}">
            <div class="text-sm text-gray-600 mb-1">Просрочено по прибытию</div>
            <div class="text-2xl font-extrabold {{ $this->stats['overdue_arrival'] > 0 ? 'text-red-700' : 'text-gray-900' }}">
                {{ $this->stats['overdue_arrival'] }}
            </div>
            <div class="text-xs text-gray-500 mt-1">Помощник опаздывает к клиенту</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Active Jobs --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Active Roadside Jobs --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Активные заявки ({{ $this->activeRoadsideJobs->count() }})
                    </h3>
                </div>
                
                <div class="divide-y divide-gray-200 max-h-[calc(100vh-400px)] overflow-y-auto">
                    @forelse($this->activeRoadsideJobs as $emergency)
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-semibold text-gray-900">
                                            Заявка #{{ $emergency->id }}
                                        </span>
                                        @if($emergency->order)
                                            <span class="text-sm text-gray-500">
                                                • Заказ #{{ $emergency->order->order_number ?? $emergency->order->id }}
                                            </span>
                                        @endif
                                        @if($emergency->is_overdue_assignment)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                ⚠️ Просрочено назначение
                                            </span>
                                        @endif
                                        @if($emergency->is_overdue_arrival)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                ⚠️ Просрочено прибытие
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @php
                                        $incidentTypes = [
                                            'jump_start' => '🔋 Прикурить',
                                            'fuel' => '⛽ Топливо',
                                            'flat_tire' => '🛞 Заменить колесо',
                                            'locked_keys' => '🔑 Открыть авто',
                                            'engine_no_start' => '🚗 Не заводится',
                                            'tow_needed' => '🚛 Эвакуация',
                                            'accident' => '⚠️ ДТП',
                                        ];
                                        $statusLabels = [
                                            'new' => 'Новый',
                                            'assigned' => 'Назначен',
                                            'on_route' => 'В пути',
                                            'in_progress' => 'В работе',
                                        ];
                                    @endphp
                                    
                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Тип:</span>
                                        {{ $incidentTypes[$emergency->incident_type] ?? $emergency->incident_type }}
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Клиент:</span>
                                        {{ $emergency->customer->name ?? ($emergency->metadata['full_name'] ?? 'N/A') }}
                                        @if($emergency->metadata['phone'] ?? null)
                                            <span class="text-gray-500">({{ $emergency->metadata['phone'] }})</span>
                                        @endif
                                    </div>
                                    
                                    @if($emergency->metadata['location_text'] ?? null)
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">Место:</span>
                                            {{ $emergency->metadata['location_text'] }}
                                        </div>
                                    @endif
                                    
                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Исполнитель:</span>
                                        @if($emergency->helper)
                                            {{ $emergency->helper->user->name ?? 'Helper #' . $emergency->helper->id }}
                                        @elseif($emergency->partner)
                                            {{ $emergency->partner->name }} (партнёр)
                                        @else
                                            <span class="text-red-600">Не назначен</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($emergency->status === 'new') bg-yellow-100 text-yellow-800
                                    @elseif($emergency->status === 'assigned') bg-blue-100 text-blue-800
                                    @elseif($emergency->status === 'on_route') bg-indigo-100 text-indigo-800
                                    @elseif($emergency->status === 'in_progress') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $statusLabels[$emergency->status] ?? $emergency->status }}
                                </span>
                            </div>
                            
                            {{-- Actions --}}
                            <div class="flex flex-wrap gap-2 mt-3">
                                @if(!$emergency->helper)
                                    <form method="POST" action="{{ route('admin.roadside.assign-helper') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="emergency_id" value="{{ $emergency->id }}">
                                        <select name="helper_id" required class="text-xs border-gray-300 rounded-md shadow-sm mr-2">
                                            <option value="">Выберите помощника</option>
                                            @foreach($this->availableHelpers as $helper)
                                                <option value="{{ $helper->id }}">
                                                    {{ $helper->user->name ?? 'Helper #' . $helper->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                            Назначить помощника
                                        </button>
                                    </form>
                                @endif
                                
                                @if(!$emergency->partner && in_array($emergency->incident_type, ['tow_needed', 'accident']))
                                    <form method="POST" action="{{ route('admin.roadside.assign-partner') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="emergency_id" value="{{ $emergency->id }}">
                                        <select name="partner_id" required class="text-xs border-gray-300 rounded-md shadow-sm mr-2">
                                            <option value="">Выберите партнёра</option>
                                            @foreach($this->availablePartners as $partner)
                                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-3 py-1 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                                            Назначить партнёра
                                        </button>
                                    </form>
                                @endif
                                
                                @if($emergency->order)
                                    <a href="{{ \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $emergency->order->id]) }}" 
                                       target="_blank"
                                       class="px-3 py-1 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                        Открыть заказ
                                    </a>
                                @endif
                                
                                @if($emergency->tracking_url)
                                    <a href="{{ $emergency->tracking_url }}" 
                                       target="_blank"
                                       class="px-3 py-1 text-xs bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                        Публичный трекинг
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            Нет активных заявок
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        
        {{-- Right Column: Available Helpers & Partners --}}
        <div class="space-y-6">
            {{-- Available Helpers --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Доступные помощники ({{ $this->availableHelpers->count() }})
                    </h3>
                </div>
                
                <div class="divide-y divide-gray-200 max-h-[calc(50vh-200px)] overflow-y-auto">
                    @forelse($this->availableHelpers as $helper)
                        <div class="p-4">
                            <div class="font-medium text-gray-900 mb-1">
                                {{ $helper->user->name ?? 'Helper #' . $helper->id }}
                            </div>
                            
                            @if($helper->user->phone ?? null)
                                <div class="text-sm text-gray-600 mb-1">
                                    📞 {{ $helper->user->phone }}
                                </div>
                            @endif
                            
                            @if($helper->vehicle_type)
                                <div class="text-sm text-gray-600 mb-1">
                                    🚗 {{ $helper->vehicle_type }}
                                    @if($helper->vehicle_model)
                                        - {{ $helper->vehicle_model }}
                                    @endif
                                </div>
                            @endif
                            
                            @if($helper->skills)
                                <div class="text-xs text-gray-500 mt-2">
                                    Навыки: {{ implode(', ', array_slice($helper->skills, 0, 3)) }}
                                    @if(count($helper->skills) > 3)
                                        +{{ count($helper->skills) - 3 }}
                                    @endif
                                </div>
                            @endif
                            
                            <div class="mt-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($helper->current_status === 'idle') bg-green-100 text-green-800
                                    @elseif($helper->current_status === 'busy') bg-yellow-100 text-yellow-800
                                    @elseif($helper->current_status === 'on_route') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ match($helper->current_status) {
                                        'idle' => 'Доступен',
                                        'busy' => 'Занят',
                                        'on_route' => 'В пути',
                                        default => $helper->current_status ?? 'Offline',
                                    } }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 text-sm">
                            Нет доступных помощников
                        </div>
                    @endforelse
                </div>
            </div>
            
            {{-- Available Partners --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Партнёры-эвакуаторы ({{ $this->availablePartners->count() }})
                    </h3>
                </div>
                
                <div class="divide-y divide-gray-200 max-h-[calc(50vh-200px)] overflow-y-auto">
                    @forelse($this->availablePartners as $partner)
                        <div class="p-4">
                            <div class="font-medium text-gray-900 mb-1">
                                {{ $partner->name }}
                            </div>
                            
                            @if($partner->type)
                                <div class="text-sm text-gray-600 mb-1">
                                    Тип: {{ match($partner->type) {
                                        'towing_service' => 'Эвакуатор',
                                        'roadside_mobile' => 'Мобильная помощь',
                                        'repair_shop' => 'СТО',
                                        default => $partner->type,
                                    } }}
                                </div>
                            @endif
                            
                            @if($partner->phone)
                                <div class="text-sm text-gray-600 mb-1">
                                    📞 {{ $partner->phone }}
                                </div>
                            @endif
                            
                            @if($partner->geoZone)
                                <div class="text-sm text-gray-600 mb-1">
                                    📍 {{ $partner->geoZone->name }}
                                </div>
                            @endif
                            
                            @if($partner->capabilities)
                                <div class="text-xs text-gray-500 mt-2">
                                    Возможности: {{ implode(', ', array_slice(array_keys($partner->capabilities), 0, 3)) }}
                                    @if(count($partner->capabilities) > 3)
                                        +{{ count($partner->capabilities) - 3 }}
                                    @endif
                                </div>
                            @endif
                            
                            @if($partner->priority)
                                <div class="mt-2">
                                    <span class="text-xs text-gray-500">
                                        Приоритет: {{ $partner->priority }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 text-sm">
                            Нет доступных партнёров
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    @if(session('status'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 3000)"
             class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('status') }}
        </div>
    @endif
    
</x-filament::page>
