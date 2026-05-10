@extends('layouts.app')

@section('title', 'Мастер на час — GLF Bikube')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-amber-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Hero Block --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">
                Мастер на час
            </h1>
            <p class="text-xl text-slate-700 mb-2">
                Профессиональные услуги мастера в Нарвике
            </p>
        </div>

        {{-- Filters --}}
        <div class="mb-8 bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex flex-wrap gap-4 items-center">
                <span class="text-sm font-medium text-slate-700">Категория:</span>
                <a href="{{ route('handyman.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm {{ !$currentCategory ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Все
                </a>
                @foreach(['plumbing' => 'Сантехника', 'electrical' => 'Электрика', 'furniture' => 'Мебель', 'other' => 'Прочее'] as $cat => $label)
                    <a href="{{ route('handyman.index', ['category' => $cat]) }}" 
                       class="px-4 py-2 rounded-lg text-sm {{ $currentCategory === $cat ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Services Grid --}}
        @if($services->isEmpty())
            <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                <p class="text-slate-600 mb-4">Услуги не найдены.</p>
                <a href="{{ route('handyman.custom-request') }}" class="text-amber-600 hover:text-amber-700 underline">
                    Создать индивидуальный запрос
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($services as $service)
                    <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-6 hover:shadow-xl transition">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $service->name }}</h3>
                            @if($service->category)
                                <span class="px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded">
                                    {{ $service->category }}
                                </span>
                            @endif
                        </div>
                        
                        @if($service->description)
                            <p class="text-sm text-slate-600 mb-4 line-clamp-3">{{ $service->description }}</p>
                        @endif

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                @if($service->pricing_mode === 'FIXED')
                                    <span class="text-lg font-bold text-slate-900">
                                        {{ number_format($service->base_rate_minor / 100, 0, ',', ' ') }} NOK
                                    </span>
                                    <span class="text-xs text-slate-500">фиксированная цена</span>
                                @else
                                    <span class="text-lg font-bold text-slate-900">
                                        {{ number_format($service->base_rate_minor / 100, 0, ',', ' ') }} NOK/час
                                    </span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('handyman.service.show', $service->slug) }}" 
                           class="block w-full text-center px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition">
                            Заказать
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Custom Request CTA --}}
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center">
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Не нашли нужную услугу?</h3>
            <p class="text-slate-600 mb-4">Опишите вашу задачу, и мы подберём мастера</p>
            <a href="{{ route('handyman.custom-request') }}" 
               class="inline-block px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition">
                Создать индивидуальный запрос
            </a>
        </div>
    </div>
</div>
@endsection

