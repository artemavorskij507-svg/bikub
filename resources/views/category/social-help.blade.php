@extends('layouts.app')

@section('title', 'Социальная помощь и забота — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                Социальная помощь и забота в Нарвике
            </h1>
            <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                Сопровождение, совместные прогулки, помощь с покупками, техническая помощь — забота о пожилых людях и людях с особыми потребностями.
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('account.new-order.care') ?? '#' }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                    Создать план заботы
                </a>
            </div>
        </header>

        {{-- Блок: Услуги --}}
        @if(isset($services) && $services->count() > 0)
            <section class="space-y-3">
                <h2 class="text-xl font-semibold">Услуги заботы</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div class="border border-gray-100 rounded-xl p-4 bg-white/70 hover:border-emerald-400 hover:shadow-sm transition">
                            <p class="text-sm font-semibold">{{ $service->name }}</p>
                            @if($service->description)
                                <p class="text-xs text-gray-500 mt-1">{{ $service->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Блок: Уровни исполнителей --}}
        <section class="space-y-3">
            <h2 class="text-xl font-semibold">Три уровня исполнителей</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Social Helper</h3>
                    <p class="text-xs text-gray-500">Базовые услуги: покупки, прогулки, сопровождение</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Community Partner</h3>
                    <p class="text-xs text-gray-500">Расширенные услуги: техническая помощь, организация</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Bikube Friend</h3>
                    <p class="text-xs text-gray-500">Персональный помощник: долгосрочная забота</p>
                </div>
            </div>
        </section>
    </section>
@endsection

