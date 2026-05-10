@extends('layouts.app')

@section('title', 'Эвакуатор и помощь на дороге — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                Эвакуатор и помощь на дороге в Нарвике
            </h1>
            <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                Эвакуатор, прикурить, замена колеса, доставка топлива, осмотр автомобиля перед покупкой — помощь на дороге 24/7.
                Часть услуг выполняют наши исполнители, тяжёлые случаи — проверенные партнёры.
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('public.roadside.order') }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                    Запросить помощь на дороге
                </a>
                <a href="{{ route('public.roadside.sos') }}"
                   class="inline-flex items-center px-4 py-2 rounded-full border border-red-600 text-red-700 text-sm font-medium hover:bg-red-50">
                    🆘 SOS
                </a>
            </div>
        </header>

        {{-- Блок: Услуги --}}
        <section class="space-y-3">
            <h2 class="text-xl font-semibold">Услуги помощи на дороге</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🚛 Эвакуатор</h3>
                    <p class="text-xs text-gray-500">Буксировка автомобиля до СТО или дома</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🔋 Прикурить</h3>
                    <p class="text-xs text-gray-500">Запуск двигателя при разряженном аккумуляторе</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🛞 Колесо</h3>
                    <p class="text-xs text-gray-500">Замена колеса, подкачка шин</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">⛽ Доставка топлива</h3>
                    <p class="text-xs text-gray-500">Доставка бензина/дизеля на место</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🔍 Осмотр перед покупкой</h3>
                    <p class="text-xs text-gray-500">Профессиональная оценка состояния автомобиля</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🆘 SOS</h3>
                    <p class="text-xs text-gray-500">Экстренная помощь в любой ситуации</p>
                </div>
            </div>
        </section>

        {{-- Блок: Услуги из БД --}}
        @if(isset($services) && $services->count() > 0)
            <section class="space-y-3">
                <h2 class="text-xl font-semibold">Доступные услуги</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                            <p class="text-sm font-semibold">{{ $service->name }}</p>
                            @if($service->description)
                                <p class="text-xs text-gray-500 mt-1">{{ $service->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </section>
@endsection

