@extends('layouts.app')

@section('title', 'GLF BiKube — Доставка и услуги в Нарвике')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

        {{-- HERO: основной CTA --}}
        <section class="grid gap-8 lg:grid-cols-2 items-center">
            <div class="space-y-4">
                <p class="text-sm uppercase tracking-wide text-emerald-500 font-semibold">
                    Narvik • GLF BiKube
                </p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">
                    Единый городской хаб доставки<br class="hidden sm:block" />
                    <span class="text-emerald-500">продукты, еда, услуги, забота</span>
                </h1>
                <p class="text-gray-500 text-base sm:text-lg">
                    Живые магазины, реальные исполнители, прозрачные тарифы. 
                    Один заказ — весь Нарвик у тебя под рукой.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('public.category', ['slug' => 'delivery']) }}"
                       class="inline-flex items-center px-5 py-3 rounded-full bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition">
                        Заказать доставку продуктов
                    </a>
                    <a href="{{ route('public.services.index') }}"
                       class="inline-flex items-center px-5 py-3 rounded-full border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">
                        Посмотреть все услуги
                    </a>
                    @auth
                        <a href="{{ route('account.orders.index') }}"
                           class="inline-flex items-center px-5 py-3 rounded-full border border-emerald-300 text-emerald-700 font-medium hover:bg-emerald-50 transition">
                            Мои заказы
                        </a>
                    @endauth
                </div>

                <div class="flex flex-wrap gap-6 text-sm text-gray-500 pt-4">
                    <span>🚚 Активных доставок: <strong>{{ $activeDeliveriesCount ?? 0 }}</strong></span>
                    <span>📦 Заказов за сегодня: <strong>{{ $todayOrdersCount ?? 0 }}</strong></span>
                </div>
            </div>

            {{-- Правая колонка: карточка "Как это работает" --}}
            <div class="bg-white/80 rounded-2xl shadow-md border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-semibold">Как работает BiKube Delivery</h2>
                <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                    <li>Выбираешь услугу: доставка, мастер, переезд, забота и др.</li>
                    <li>Заполняешь адрес и время — система подбирает оптимальный сценарий.</li>
                    <li>Курьер или исполнитель приезжает, всё фиксируется в приложении.</li>
                    <li>Оплата, отчёт, рейтинг — всё в одном Личном кабинете.</li>
                </ol>
                <p class="text-xs text-gray-400">
                    Тарифы считаются автоматически: учёт зоны, расстояния, объёма и нагрузки по городу.
                </p>
            </div>
        </section>

        {{-- Блок 1: Категории услуг (основные направления) --}}
        <section class="space-y-4">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">Направления сервиса</h2>
            </div>

            @if($categories->isEmpty())
                <p class="text-gray-500 text-sm">
                    Категории услуг пока не настроены. Заполни таблицу <code>service_categories</code> через сидер или админку.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($categories as $category)
                        <a href="{{ route('public.category', ['slug' => $category->slug ?? $category->code]) }}"
                           class="group rounded-2xl border border-gray-100 bg-white/60 p-5 hover:border-emerald-400 hover:shadow-md transition flex flex-col justify-between">
                            <div class="space-y-2">
                                <p class="text-xs uppercase tracking-wide text-gray-400">
                                    {{ $category->code ?? $category->slug }}
                                </p>
                                <h3 class="text-lg font-semibold">
                                    {{ $category->name }}
                                </h3>
                                @if($category->description)
                                    <p class="text-sm text-gray-500 line-clamp-3">
                                        {{ $category->description }}
                                    </p>
                                @endif
                            </div>
                            <div class="mt-4 text-xs text-emerald-600 font-medium flex items-center gap-1">
                                <span>Перейти в раздел</span>
                                <span aria-hidden="true">↗</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Блок 2: Продуктовые магазины для Grocery Delivery --}}
        <section class="space-y-4">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">Магазины для доставки продуктов</h2>
                @if(isset($featuredStores) && $featuredStores->count() > 0)
                    <a href="{{ route('public.stores.index') }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все магазины
                    </a>
                @endif
            </div>

            @if(!isset($featuredStores) || $featuredStores->isEmpty())
                <p class="text-gray-500 text-sm">
                    В таблице магазинов пока пусто. Проверь сидер <code>RealWorldCatalogSeeder::seedStores()</code>.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($featuredStores as $store)
                        <a href="{{ route('public.stores.show', $store->slug) }}"
                           class="group rounded-2xl border border-gray-100 bg-white/60 p-4 hover:border-emerald-400 hover:shadow-md transition flex flex-col justify-between">
                            <div class="space-y-2">
                                <h3 class="text-base font-semibold">
                                    {{ $store->brand ?? $store->name }}
                                </h3>
                                <p class="text-xs text-gray-500 line-clamp-2">
                                    {{ $store->address ?? $store->city }}
                                </p>
                            </div>
                            <div class="mt-3 text-xs text-gray-400">
                                {{ $store->city ?? 'Narvik' }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Блок 3: Рестораны для FOOD-доставки --}}
        <section class="space-y-4">
            <div class="flex items-baseline justify-between">
                <h2 class="text-xl font-semibold">Рестораны и кафе (доставка еды)</h2>
                @if(isset($featuredRestaurants) && $featuredRestaurants->count() > 0)
                    <a href="{{ route('public.restaurants.index') }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все рестораны
                    </a>
                @endif
            </div>

            @if(!isset($featuredRestaurants) || $featuredRestaurants->isEmpty())
                <p class="text-gray-500 text-sm">
                    Рестораны ещё не заведены. Проверь сидер <code>RealWorldCatalogSeeder::seedRestaurants()</code>.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($featuredRestaurants as $restaurant)
                        <a href="{{ route('public.restaurants.show', $restaurant->slug) }}"
                           class="group rounded-2xl border border-gray-100 bg-white/60 p-4 hover:border-emerald-400 hover:shadow-md transition flex flex-col justify-between">
                            <div class="space-y-1">
                                <h3 class="text-base font-semibold">
                                    {{ $restaurant->name }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    {{ $restaurant->cuisine_type ? ucfirst($restaurant->cuisine_type) : 'Ресторан' }}
                                </p>
                                <p class="text-xs text-gray-500 line-clamp-2">
                                    {{ $restaurant->address ?? $restaurant->city }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- CTA для крупногабарита --}}
        <section class="space-y-4">
            <a href="{{ route('public.category', ['slug' => 'delivery']) }}?type=bulky"
               class="block rounded-xl border-2 border-blue-200 bg-blue-50 p-6 hover:bg-blue-100 transition text-center">
                <h3 class="text-xl font-bold text-blue-900">Доставка крупногабарита</h3>
                <p class="text-blue-700 mt-2">Мебель, техника, стройматериалы — доставим быстро и аккуратно</p>
            </a>
        </section>

        {{-- Блок 4: Популярные услуги мастера --}}
        @if(isset($handymanPopular) && $handymanPopular->count() > 0)
            <section class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-xl font-semibold">Популярные услуги мастера</h2>
                    <a href="{{ route('public.category', ['slug' => 'handyman']) }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все услуги
                    </a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($handymanPopular as $service)
                        <a href="{{ route('public.category', ['slug' => 'handyman']) }}?service={{ $service->slug }}"
                           class="group rounded-2xl border border-gray-100 bg-white/60 p-4 hover:border-emerald-400 hover:shadow-md transition">
                            <h3 class="text-base font-semibold">{{ $service->name }}</h3>
                            @if($service->description)
                                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $service->description }}</p>
                            @endif
                            <p class="text-sm text-emerald-600 mt-2 font-medium">
                                От {{ number_format($service->base_rate_minor / 100, 0) }} NOK
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Блок 5: Эко-услуги --}}
        @if(isset($ecoCategories) && $ecoCategories->count() > 0)
            <section class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-xl font-semibold">Эко-услуги и утилизация</h2>
                    <a href="{{ route('public.category', ['slug' => 'eco']) }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все услуги
                    </a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($ecoCategories as $eco)
                        <a href="{{ route('public.category', ['slug' => 'eco']) }}?service={{ $eco->slug }}"
                           class="group rounded-2xl border border-gray-100 bg-white/60 p-4 hover:border-emerald-400 hover:shadow-md transition">
                            <h3 class="text-base font-semibold">{{ $eco->name }}</h3>
                            @if($eco->description)
                                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $eco->description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Блок 6: Последние доставки (превью) --}}
        @if(isset($recentDeliveries) && $recentDeliveries->count() > 0)
            <section class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-xl font-semibold">Активные доставки</h2>
                    <a href="{{ route('public.category', ['slug' => 'delivery']) }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все доставки
                    </a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($recentDeliveries as $delivery)
                        <div class="rounded-2xl border border-gray-100 bg-white/60 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-gray-500">#{{ $delivery->id }}</span>
                                <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                    {{ $delivery->tracking_status?->label() ?? 'Неизвестно' }}
                                </span>
                            </div>
                            @if($delivery->eta)
                                <p class="text-xs text-gray-500 mt-2">
                                    ETA: {{ $delivery->eta->format('H:i') }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Блок 7: Индивидуальные поручения --}}
        @if(isset($errandExamples) && $errandExamples->count() > 0)
            <section class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-xl font-semibold">Индивидуальные поручения</h2>
                    <a href="{{ route('public.category', ['slug' => 'personal-task']) }}"
                       class="text-sm text-emerald-600 hover:text-emerald-700">
                        Все поручения
                    </a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($errandExamples as $errand)
                        <div class="rounded-2xl border border-gray-100 bg-white/60 p-4">
                            <h3 class="text-base font-semibold">Поручение #{{ $errand->id }}</h3>
                            @if($errand->description)
                                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $errand->description }}</p>
                            @endif
                            <span class="text-xs text-gray-400 mt-2 block">
                                {{ $errand->created_at->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
@endsection

