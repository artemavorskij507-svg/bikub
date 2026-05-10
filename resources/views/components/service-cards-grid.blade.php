{{-- resources/views/components/service-cards-grid.blade.php --}}
@props(['services' => null, 'title' => 'Наші сервіси'])

@php
    $services = $services ?? App\Models\ServiceType::where('is_active', true)
        ->with('serviceCategory')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->take(6)
        ->get();
@endphp

@if($services->count() > 0)
<section class="py-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12 text-slate-900">{{ $title }}</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @foreach($services as $service)
                <div class="bg-white rounded-2xl overflow-hidden shadow-xl border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                    <!-- Контент картки -->
                    <div class="p-6 h-full flex flex-col">
                        <!-- Іконка -->
                        <div class="mb-4 text-sky-500">
                            @php
                                $icon = $service->icon ?? $service->serviceCategory?->icon;
                                $isSvg = $icon && (str_starts_with(trim($icon), '<svg') || str_starts_with(trim($icon), '<SVG'));
                            @endphp
                            @if($icon)
                                @if($isSvg)
                                    <div class="w-12 h-12 flex items-center justify-center">
                                        {!! $icon !!}
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center text-2xl">
                                        {{ $icon }}
                                    </div>
                                @endif
                            @else
                                <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Заголовок -->
                        <h3 class="text-xl font-bold mb-2 text-slate-900">{{ $service->name }}</h3>
                        
                        <!-- Опис -->
                        <p class="text-gray-600 mb-4 flex-grow line-clamp-3">
                            {{ Str::limit($service->description ?? $service->serviceCategory?->description ?? 'Опис послуги', 120) }}
                        </p>
                        
                        <!-- Категорія -->
                        @if($service->serviceCategory)
                            <div class="mb-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-700">
                                    {{ $service->serviceCategory->name }}
                                </span>
                            </div>
                        @endif
                        
                        <!-- CTA кнопка -->
                        @php
                            $categoryCode = $service->serviceCategory?->code ?? $service->category ?? null;
                            $serviceUrl = $categoryCode 
                                ? route('public.catalog.index', ['category' => $categoryCode])
                                : route('public.catalog.index');
                        @endphp
                        <a 
                            href="{{ $serviceUrl }}"
                            class="inline-flex items-center justify-center px-4 py-2 bg-sky-600 text-white font-semibold rounded-lg hover:bg-sky-700 transition-colors self-start mt-auto"
                        >
                            Дізнатися більше
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

