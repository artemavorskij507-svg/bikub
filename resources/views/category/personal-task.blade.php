@extends('layouts.app')

@section('title', 'Индивидуальные поручения — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                Индивидуальные поручения в Нарвике
            </h1>
            <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                Универсальный личный помощник для нестандартных задач: курьер, документы, покупки, очередь, «глаза и руки» — мы выполним то, что не входит в стандартные услуги.
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                @auth
                    <a href="{{ route('account.errands.create') }}"
                       class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                        Создать поручение
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                        Создать поручение
                    </a>
                @endauth
            </div>
        </header>

        {{-- Блок: Категории поручений --}}
        <section class="space-y-3">
            <h2 class="text-xl font-semibold">Категории поручений</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">📦 Курьер</h3>
                    <p class="text-xs text-gray-500">Доставка документов, посылок, покупок</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">📄 Документы</h3>
                    <p class="text-xs text-gray-500">Подача/получение документов в учреждениях</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🛒 Покупки</h3>
                    <p class="text-xs text-gray-500">Покупка конкретных товаров по списку</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">⏰ Очередь</h3>
                    <p class="text-xs text-gray-500">Занять очередь, подождать твоей очереди</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">👀 Глаза и руки</h3>
                    <p class="text-xs text-gray-500">Проверить, сфотографировать, оценить</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-4 bg-white/70">
                    <h3 class="text-sm font-semibold mb-2">🔧 Другое</h3>
                    <p class="text-xs text-gray-500">Любые нестандартные задачи</p>
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

