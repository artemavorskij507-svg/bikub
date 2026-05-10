@extends('layouts.app')

@section('title', 'Эко-услуги и утилизация — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                Эко-услуги и утилизация в Нарвике
            </h1>
            <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                Вывоз мебели, техники, строймусора, одежды с акцентом на переработку и донейт. Мы не просто вывозим — мы даём вещам вторую жизнь.
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('account.new-order.eco') ?? '#' }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                    Заказать эко-вывоз
                </a>
            </div>
        </header>

        {{-- Блок: Типы утилизации --}}
        <section class="space-y-3">
            <h2 class="text-xl font-semibold">Что мы принимаем</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Мебель</h3>
                    <p class="text-xs text-gray-500">Диваны, столы, шкафы — переработка или донейт</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Техника</h3>
                    <p class="text-xs text-gray-500">Электроника, бытовая техника — утилизация</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Строймусор</h3>
                    <p class="text-xs text-gray-500">Остатки ремонта, материалы — вывоз</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">Одежда</h3>
                    <p class="text-xs text-gray-500">Текстиль — донейт или переработка</p>
                </div>
            </div>
        </section>

        {{-- Блок: Пояснения --}}
        <section class="space-y-3">
            <h2 class="text-xl font-semibold">Наши принципы</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">♻️ RECYCLE</h3>
                    <p class="text-xs text-gray-500">Переработка материалов</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">❤️ DONATE</h3>
                    <p class="text-xs text-gray-500">Передача нуждающимся</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">⚠️ HAZARDOUS</h3>
                    <p class="text-xs text-gray-500">Безопасная утилизация опасных отходов</p>
                </div>
            </div>
        </section>

        {{-- Блок: Услуги --}}
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

