<x-filament::widget>
    <x-filament::card>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Управление главной страницей</h3>
            <p class="text-sm text-gray-600 mt-1">Управляйте контентом главной страницы: слайдер, услуги, секции</p>
        </div>

        <div class="space-y-6">
            {{-- Статистика --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Услуг</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_services'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Рекомендованных</div>
                    <div class="text-2xl font-bold text-primary-600">{{ $stats['featured_services'] }}</div>
                </div>
            </div>

            {{-- Быстрые действия --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a 
                    href="{{ \Filament\Facades\Filament::getUrl('resources/service-types') }}"
                    class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition"
                >
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Услуги</h3>
                            <p class="text-sm text-gray-500">Управляйте услугами на главной</p>
                        </div>
                    </div>
                </a>

                <a 
                    href="{{ \Filament\Facades\Filament::getUrl('resources/feature-flags') }}"
                    class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition"
                >
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Feature Flags</h3>
                            <p class="text-sm text-gray-500">A/B тестирование и функции</p>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Превью услуг --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Рекомендованные услуги</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @forelse($services->take(8) as $service)
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center space-x-3">
                                @if($service->icon)
                                    <div class="flex-shrink-0 text-2xl">{{ $service->icon }}</div>
                                @else
                                    <div class="flex-shrink-0 w-8 h-8 bg-primary-100 rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 truncate">{{ $service->name }}</h4>
                                    @if($service->serviceCategory)
                                        <p class="text-xs text-gray-500">{{ $service->serviceCategory->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400">Порядок: {{ $service->sort_order ?? 0 }}</span>
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    Активный
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-4 text-center py-8 text-gray-500">
                            Нет активных услуг. <a href="{{ \Filament\Facades\Filament::getUrl('resources/service-types/create') }}" class="text-primary-600 hover:underline">Создать услугу</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>

