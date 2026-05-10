@extends('layouts.app')

@section('title', 'Доска объявлений — GLF Bikube')
@section('meta_description', 'Покупайте и продавайте товары в Норвегии. Большой выбор объявлений от частных лиц и магазинов.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
    {{-- Enhanced Hero Section --}}
    <div class="relative bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 text-white overflow-hidden">
        <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-primary-900/50"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
            <div class="text-center">
                <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tight">
                    <span class="bg-gradient-to-r from-yellow-300 via-orange-300 to-yellow-300 bg-clip-text text-transparent">
                        Доска объявлений
                    </span>
                </h1>
                <p class="text-xl md:text-3xl text-primary-100 mb-4 font-medium">
                    Покупайте и продавайте в Норвегии
                </p>
                <p class="text-lg text-primary-200 mb-10 max-w-2xl mx-auto">
                    Более {{ number_format($stats['total'], 0, ',', ' ') }} объявлений от проверенных продавцов
                </p>
                
                {{-- Quick Search --}}
                <div class="max-w-3xl mx-auto mb-8">
                    <form method="GET" action="{{ route('classifieds.index') }}" class="flex gap-2 relative">
                        <div class="flex-1 relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" 
                                   name="q" 
                                   value="{{ request('q') }}"
                                   placeholder="Что вы ищете? Например: iPhone, велосипед, мебель..." 
                                   class="w-full pl-12 pr-4 py-4 rounded-xl text-slate-900 text-lg focus:outline-none focus:ring-4 focus:ring-yellow-300 shadow-xl border-0">
                        </div>
                        <button type="submit" 
                                class="px-8 py-4 bg-yellow-400 hover:bg-yellow-300 text-primary-900 font-bold rounded-xl shadow-xl transition-all transform hover:scale-105 active:scale-95 text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Поиск
                        </button>
                    </form>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @auth
                        <a href="{{ route('account.classifieds.create') }}" 
                           class="group bg-white text-primary-600 hover:bg-primary-50 font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg flex items-center justify-center gap-2">
                            <svg class="w-6 h-6 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            📝 Разместить объявление
                        </a>
                        <a href="{{ route('account.classifieds.shop') }}" 
                           class="bg-primary-500/90 hover:bg-primary-400 text-white font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg flex items-center justify-center gap-2">
                            🏪 Открыть магазин
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="bg-white text-primary-600 hover:bg-primary-50 font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg">
                            Войти для размещения
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="bg-gradient-to-br from-white to-slate-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-primary-300 p-6 hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-default group">
                <div class="text-4xl mb-3 transform group-hover:scale-110 transition-transform">📦</div>
                <div class="text-xs uppercase tracking-wider text-slate-500 mb-2 font-semibold">Всего объявлений</div>
                <div class="text-4xl font-black text-slate-900 leading-none">{{ number_format($stats['total'], 0, ',', ' ') }}</div>
            </div>
            <div class="bg-gradient-to-br from-white to-primary-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-primary-300 p-6 hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-default group">
                <div class="text-4xl mb-3 transform group-hover:scale-110 transition-transform">💰</div>
                <div class="text-xs uppercase tracking-wider text-slate-500 mb-2 font-semibold">С указанной ценой</div>
                <div class="text-4xl font-black text-primary-600 leading-none">{{ number_format($stats['with_price'], 0, ',', ' ') }}</div>
            </div>
            <div class="bg-gradient-to-br from-white to-green-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-green-300 p-6 hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-default group">
                <div class="text-4xl mb-3 transform group-hover:scale-110 transition-transform">🏪</div>
                <div class="text-xs uppercase tracking-wider text-slate-500 mb-2 font-semibold">Магазинов</div>
                <div class="text-4xl font-black text-green-600 leading-none">{{ number_format($stats['shops'], 0, ',', ' ') }}</div>
            </div>
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-purple-300 p-6 hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-default group">
                <div class="text-4xl mb-3 transform group-hover:scale-110 transition-transform">📂</div>
                <div class="text-xs uppercase tracking-wider text-slate-500 mb-2 font-semibold">Категорий</div>
                <div class="text-4xl font-black text-purple-600 leading-none">{{ number_format($stats['categories'], 0, ',', ' ') }}</div>
            </div>
        </div>

        {{-- Popular Categories Grid --}}
        @if($popularCategories->count() > 0)
            <div class="mb-16">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-black text-slate-900">Популярные категории</h2>
                    <a href="{{ route('classifieds.index') }}" class="text-primary-600 hover:text-primary-700 font-semibold flex items-center gap-2">
                        Все категории <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach($popularCategories as $category)
                        <a href="{{ route('classifieds.index', ['category' => $category->id]) }}" 
                           class="group bg-gradient-to-br from-white to-slate-50 rounded-2xl shadow-md border-2 border-slate-200 hover:border-primary-400 hover:shadow-2xl transition-all p-6 text-center transform hover:-translate-y-3 hover:scale-105">
                            <div class="text-5xl mb-4 transform group-hover:scale-125 group-hover:rotate-6 transition-all duration-300">📦</div>
                            <h3 class="font-black text-slate-900 mb-2 group-hover:text-primary-600 transition text-base">{{ $category->name }}</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">{{ number_format($category->ads_count ?? 0, 0, ',', ' ') }} объявлений</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Featured Ads (VIP/Premium) --}}
        @if($featuredAds->count() > 0)
            <div class="mb-16">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span class="text-yellow-500">⭐</span> Премиум объявления
                    </h2>
                    <a href="{{ route('classifieds.index', ['sort' => 'newest']) }}" class="text-primary-600 hover:text-primary-700 font-semibold flex items-center gap-2">
                        Смотреть все <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($featuredAds as $ad)
                        @include('classifieds.partials.ad-card', ['ad' => $ad, 'featured' => true])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Recommended Ads --}}
        @if($recommendedAds->count() > 0 && !request()->hasAny(['q', 'category', 'price_min', 'price_max']))
            <div class="mb-16">
                <h2 class="text-3xl font-black text-slate-900 mb-6 flex items-center gap-3">
                    <span class="text-green-500">🔥</span> Рекомендуем
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($recommendedAds as $ad)
                        @include('classifieds.partials.ad-card', ['ad' => $ad])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Popular Shops --}}
        @if($popularShops->count() > 0)
            <div class="mb-16">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span class="text-blue-500">🏪</span> Популярные магазины
                    </h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($popularShops as $shop)
                        <a href="{{ route('shops.show', $shop->slug) }}" 
                           class="group bg-white rounded-2xl shadow-md border-2 border-slate-200 hover:border-primary-400 hover:shadow-xl transition-all overflow-hidden transform hover:-translate-y-2">
                            <div class="h-32 bg-gradient-to-br from-primary-100 to-primary-200 relative">
                                @if($shop->cover_path)
                                    <img src="{{ asset('storage/'.$shop->cover_path) }}" class="w-full h-full object-cover">
                                @endif
                                <div class="absolute -bottom-8 left-6">
                                    @if($shop->logo_path)
                                        <img src="{{ asset('storage/'.$shop->logo_path) }}" class="w-16 h-16 rounded-xl border-4 border-white shadow-lg bg-white object-contain">
                                    @else
                                        <div class="w-16 h-16 rounded-xl border-4 border-white shadow-lg bg-slate-200"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="pt-10 px-6 pb-6">
                                <h3 class="font-bold text-lg text-slate-900 mb-1 group-hover:text-primary-600 transition">
                                    {{ $shop->name }}
                                    @if($shop->is_verified)
                                        <span class="text-blue-500" title="Проверенный магазин">✓</span>
                                    @endif
                                </h3>
                                <p class="text-sm text-slate-600 mb-3 line-clamp-2">{{ $shop->description }}</p>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">{{ $shop->ads_count }} объявлений</span>
                                    <span class="text-primary-600 font-semibold group-hover:underline">Смотреть →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Mobile Filter Toggle --}}
        <div class="lg:hidden mb-6">
            <button onclick="document.getElementById('mobile-filters').classList.toggle('hidden')" 
                    class="w-full bg-white rounded-xl shadow-lg border-2 border-slate-200 p-4 flex items-center justify-between font-bold text-slate-900 hover:border-primary-400 transition">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Фильтры
                </span>
                <svg class="w-5 h-5 transform transition-transform" id="filter-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>

        {{-- Main Content with Filters --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Sidebar with Filters --}}
            <aside class="lg:col-span-1 hidden lg:block" id="desktop-filters">
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                    <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Фильтры
                    </h2>
                    
                    <form method="GET" action="{{ route('classifieds.index') }}" class="space-y-6">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Поиск</label>
                            <input type="text" name="q" value="{{ request('q') }}" 
                                   placeholder="Название или описание..."
                                   class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Категория</label>
                            <select name="category" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">Все категории</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->ads_count ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Price Range --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Цена (NOK)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" 
                                       placeholder="От"
                                       class="px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <input type="number" name="price_max" value="{{ request('price_max') }}" 
                                       placeholder="До"
                                       class="px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                        </div>

                        {{-- Has Price --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Тип цены</label>
                            <select name="has_price" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">Все</option>
                                <option value="yes" {{ request('has_price') === 'yes' ? 'selected' : '' }}>С указанной ценой</option>
                                <option value="no" {{ request('has_price') === 'no' ? 'selected' : '' }}>По договорённости</option>
                            </select>
                        </div>

                        {{-- Seller Type --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Тип продавца</label>
                            <select name="seller_type" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">Все</option>
                                <option value="private" {{ request('seller_type') === 'private' ? 'selected' : '' }}>Частные лица</option>
                                <option value="shop" {{ request('seller_type') === 'shop' ? 'selected' : '' }}>Магазины</option>
                            </select>
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Дата публикации</label>
                            <select name="date" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">Все время</option>
                                <option value="today" {{ request('date') === 'today' ? 'selected' : '' }}>Сегодня</option>
                                <option value="week" {{ request('date') === 'week' ? 'selected' : '' }}>За неделю</option>
                                <option value="month" {{ request('date') === 'month' ? 'selected' : '' }}>За месяц</option>
                            </select>
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Сортировка</label>
                            <select name="sort" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Сначала новые</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Цена: по возрастанию</option>
                                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Цена: по убыванию</option>
                                <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>По популярности</option>
                            </select>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-all font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                Применить
                            </button>
                            <a href="{{ route('classifieds.index') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all font-semibold">
                                Сбросить
                            </a>
                        </div>
                    </form>

                    {{-- Popular Categories --}}
                    @if($popularCategories->isNotEmpty())
                        <div class="mt-8 pt-6 border-t border-slate-200">
                            <h3 class="text-sm font-bold text-slate-900 mb-4">Популярные категории</h3>
                            <div class="space-y-2">
                                @foreach($popularCategories as $category)
                                    <a href="{{ route('classifieds.index', ['category' => $category->id]) }}" 
                                       class="block text-sm text-slate-600 hover:text-primary-600 transition-colors font-medium py-1">
                                        {{ $category->name }} <span class="text-slate-400">({{ $category->ads_count }})</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </aside>

            {{-- Mobile Filters --}}
            <aside class="lg:hidden hidden mb-6" id="mobile-filters">
                <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 p-6">
                    <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Фильтры
                    </h2>
                    
                    <form method="GET" action="{{ route('classifieds.index') }}" class="space-y-4">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Поиск</label>
                            <input type="text" name="q" value="{{ request('q') }}" 
                                   placeholder="Название или описание..."
                                   class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Категория</label>
                            <select name="category" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="">Все категории</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->ads_count ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Price Range --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Цена (NOK)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" 
                                       placeholder="От"
                                       class="px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <input type="number" name="price_max" value="{{ request('price_max') }}" 
                                       placeholder="До"
                                       class="px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Сортировка</label>
                            <select name="sort" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Сначала новые</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Цена: по возрастанию</option>
                                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Цена: по убыванию</option>
                                <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>По популярности</option>
                            </select>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-all font-bold shadow-lg">
                                Применить
                            </button>
                            <a href="{{ route('classifieds.index') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all font-semibold">
                                Сбросить
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Main Content --}}
            <main class="lg:col-span-3">
                {{-- Results Header --}}
                <div class="bg-gradient-to-r from-white to-slate-50 rounded-2xl shadow-lg border-2 border-slate-200 p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                            <span>Найдено объявлений:</span>
                            <span class="text-primary-600 bg-primary-100 px-3 py-1 rounded-lg">{{ $ads->total() }}</span>
                        </h2>
                        @if(request()->hasAny(['q', 'category', 'price_min', 'price_max', 'has_price', 'seller_type', 'date']))
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="text-xs text-slate-500 font-semibold">Активные фильтры:</span>
                                @if(request('q'))
                                    <span class="px-2 py-1 bg-primary-100 text-primary-700 rounded-full text-xs font-bold">{{ request('q') }}</span>
                                @endif
                                @if(request('category'))
                                    @php $cat = $categories->firstWhere('id', request('category')); @endphp
                                    @if($cat)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">{{ $cat->name }}</span>
                                    @endif
                                @endif
                                @if(request('price_min') || request('price_max'))
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                                        {{ request('price_min') ? request('price_min') : '0' }} - {{ request('price_max') ? request('price_max') : '∞' }} NOK
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    @auth
                        <a href="{{ route('account.classifieds.create') }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-all font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Разместить объявление
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-all font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Разместить объявление
                        </a>
                    @endauth
                </div>

                {{-- Ads Grid --}}
                @if($ads->isEmpty())
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-16 text-center">
                        <svg class="w-24 h-24 mx-auto text-slate-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-2xl font-black text-slate-900 mb-3">Объявления не найдены</h3>
                        <p class="text-slate-600 mb-8 text-lg">
                            @if(request()->hasAny(['q', 'category', 'price_min', 'price_max', 'has_price', 'seller_type', 'date']))
                                Попробуйте изменить параметры поиска или сбросить фильтры
                            @else
                                Пока нет активных объявлений. Станьте первым!
                            @endif
                        </p>
                        @if(request()->hasAny(['q', 'category', 'price_min', 'price_max', 'has_price', 'seller_type', 'date']))
                            <a href="{{ route('classifieds.index') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition-all font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                Сбросить фильтры
                            </a>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($ads as $ad)
                            @include('classifieds.partials.ad-card', ['ad' => $ad])
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($ads->hasPages())
                        <div class="mt-12 flex justify-center">
                            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-4">
                                {{ $ads->links() }}
                            </div>
                        </div>
                    @endif
                @endif
            </main>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Mobile filter toggle animation
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('[onclick*="mobile-filters"]');
        const filterArrow = document.getElementById('filter-arrow');
        const mobileFilters = document.getElementById('mobile-filters');
        
        if (toggleBtn && filterArrow && mobileFilters) {
            toggleBtn.addEventListener('click', function() {
                if (mobileFilters.classList.contains('hidden')) {
                    filterArrow.style.transform = 'rotate(180deg)';
                } else {
                    filterArrow.style.transform = 'rotate(0deg)';
                }
            });
        }
    });
</script>
@endpush
@endsection
