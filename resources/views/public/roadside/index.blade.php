@extends('layouts.app')

@section('title', 'Эвакуатор и помощь на дороге')

@section('content')
<div class="max-w-4xl mx-auto py-10 space-y-8 px-6">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-4">Эвакуатор и помощь на дороге</h1>
        <p class="text-lg text-slate-700">
            Решаем любые дорожные проблемы — от прикуривания до эвакуации.
        </p>
    </div>

    <h2 class="text-xl font-semibold text-slate-900 mb-4">Виды помощи</h2>
    
    @if($presets->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            @foreach($presets as $serviceType => $presetsGroup)
                @foreach($presetsGroup as $preset)
                    <div class="border rounded-xl p-4 shadow-sm bg-white hover:shadow-md transition">
                        <h3 class="font-semibold text-slate-900 mb-2">{{ $preset->label }}</h3>
                        @if($preset->description)
                            <p class="text-sm text-slate-600 mb-3">{{ $preset->description }}</p>
                        @endif
                        @if($preset->base_price)
                            <p class="text-sky-700 font-bold">{{ number_format($preset->base_price, 0, ',', ' ') }} kr</p>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
            <p class="text-yellow-800">Услуги временно недоступны. Пожалуйста, попробуйте позже.</p>
        </div>
    @endif

    <div class="text-center">
        <a href="{{ route('public.roadside.order') }}"
           class="inline-block px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors font-semibold">
            Вызвать помощь
        </a>
    </div>
</div>
@endsection
