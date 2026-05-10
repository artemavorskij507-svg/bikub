{{-- TODO fixed by Cursor: internal debug view for Roadside module, not part of public/admin UX --}}
<x-filament::page>
    <div class="space-y-6">
        {{-- Statistics Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Партнёры</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $this->getStats()['partners'] }}</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Помощники</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $this->getStats()['helpers'] }}</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Пресеты Roadside</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $this->getStats()['roadside_presets'] }}</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500">Пресеты осмотра</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $this->getStats()['inspection_presets'] }}</div>
                </div>
            </x-filament::card>
        </div>

        {{-- Active Requests --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500 mb-2">Экстренные вызовы</div>
                    <div class="text-xl font-bold text-gray-900">
                        {{ $this->getStats()['active_emergencies'] }} / {{ $this->getStats()['total_emergencies'] }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">активных / всего</div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="p-4">
                    <div class="text-sm font-medium text-gray-500 mb-2">Заявки на осмотр</div>
                    <div class="text-xl font-bold text-gray-900">
                        {{ $this->getStats()['active_inspections'] }} / {{ $this->getStats()['total_inspections'] }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">активных / всего</div>
                </div>
            </x-filament::card>
        </div>

        {{-- Demo Data Button --}}
        <x-filament::card>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Демо-данные</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Заполнить модуль Roadside & Tow демо-данными (партнёры, помощники, пресеты, заявки).
                    Команда выполняется только в окружении local или testing.
                </p>
                <x-filament::button
                    wire:click="seedDemoData"
                    wire:loading.attr="disabled"
                    color="primary"
                    icon="heroicon-o-sparkles"
                >
                    <span wire:loading.remove>Заполнить демо-данными</span>
                    <span wire:loading>Заполнение...</span>
                </x-filament::button>
            </div>
        </x-filament::card>

        {{-- Recent Emergencies --}}
        @if($this->getRecentEmergencies()->isNotEmpty())
            <x-filament::card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Последние экстренные вызовы</h3>
                    <div class="space-y-3">
                        @foreach($this->getRecentEmergencies() as $emergency)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ $emergency->incident_type }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Клиент: {{ $emergency->customer->name ?? 'N/A' }}
                                        @if($emergency->helper)
                                            | Помощник: {{ $emergency->helper->user->name ?? 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($emergency->status === 'completed') bg-green-100 text-green-800
                                        @elseif($emergency->status === 'in_progress') bg-blue-100 text-blue-800
                                        @elseif($emergency->status === 'assigned') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $emergency->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-filament::card>
        @endif

        {{-- Recent Inspection Requests --}}
        @if($this->getRecentInspections()->isNotEmpty())
            <x-filament::card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Последние заявки на осмотр</h3>
                    <div class="space-y-3">
                        @foreach($this->getRecentInspections() as $inspection)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ $inspection->preset->title ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Клиент: {{ $inspection->customer->name ?? 'N/A' }}
                                        @if($inspection->helper)
                                            | Помощник: {{ $inspection->helper->user->name ?? 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($inspection->status === 'completed') bg-green-100 text-green-800
                                        @elseif($inspection->status === 'in_progress') bg-blue-100 text-blue-800
                                        @elseif($inspection->status === 'assigned') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $inspection->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>

