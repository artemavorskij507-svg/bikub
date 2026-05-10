@php
    $data = $this->getViewData();
    $newPublicRequests = $data['newPublicRequests'];
    $totalNewPublic = $data['totalNewPublic'];
@endphp

<x-filament::widget>
    <x-filament::card>
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Новые публичные запросы</h3>
                @if($totalNewPublic > 0)
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        {{ $totalNewPublic }} новых
                    </span>
                @endif
            </div>

            @if($newPublicRequests->isEmpty())
                <p class="text-sm text-gray-500">Нет новых публичных запросов</p>
            @else
                <div class="space-y-3">
                    @foreach($newPublicRequests as $request)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-gray-900">
                                        {{ $request->customer->name ?? ($request->metadata['full_name'] ?? 'N/A') }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $request->metadata['phone'] ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    @php
                                        $typeLabel = match($request->incident_type) {
                                            'tow_needed' => '🚛 Эвакуатор',
                                            'jump_start' => '🔋 Прикуривание',
                                            'fuel' => '⛽ Топливо',
                                            'flat_tire' => '🛞 Прокол',
                                            default => 'Помощь на дороге',
                                        };
                                    @endphp
                                    {{ $typeLabel }}
                                    @if($request->metadata['location_text'] ?? null)
                                        • {{ Str::limit($request->metadata['location_text'], 40) }}
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $request->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($request->order_id)
                                    <a href="{{ \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $request->order_id]) }}"
                                       target="_blank"
                                       class="px-3 py-1 bg-sky-600 text-white rounded text-sm hover:bg-sky-700 transition">
                                        Заказ
                                    </a>
                                @else
                                    <a href="{{ \App\Filament\Resources\RoadsideEmergencyResource::getUrl('edit', ['record' => $request->id]) }}"
                                       target="_blank"
                                       class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                        Открыть
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($totalNewPublic > 5)
                    <div class="mt-4 text-center">
                        <a href="{{ \App\Filament\Resources\RoadsideEmergencyResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'new']]]) }}"
                           class="text-sm text-sky-600 hover:underline">
                            Показать все ({{ $totalNewPublic }})
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>

