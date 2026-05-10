<x-filament::widget>
    <x-filament::card>
        <x-slot name="heading">
            Источники roadside-заказов (30 дней)
        </x-slot>

        <div class="space-y-4">
            @php
                $data = $this->getViewData();
                $breakdown = $data['breakdown'];
                $total = $data['total'];
            @endphp

            @if($total > 0)
                <div class="space-y-2">
                    @foreach($breakdown as $source => $count)
                        @if($count > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">{{ $source }}</span>
                                <div class="flex items-center gap-3">
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-primary-600 h-2 rounded-full transition-all"
                                            style="width: {{ ($count / $total) * 100 }}%"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $count }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">Нет данных за последние 30 дней</p>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>

