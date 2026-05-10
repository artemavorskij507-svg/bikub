@extends('layouts.app')

@section('title', 'Professional Delivery Service | GLF Express Narvik')

@section('content')
<style>
    [x-cloak] { display: none !important; }

    .delivery-hero-bg {
        transition: transform .8s ease, filter .8s ease;
    }

    .delivery-hero-bg:hover {
        transform: scale(1.02);
        filter: saturate(1.08);
    }

    .delivery-card {
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .delivery-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, .14);
    }

    .catalog-expand {
        animation: catalogExpand .18s ease;
    }

    .small-catalog-highlight {
        background:
            radial-gradient(1200px 220px at 0% 0%, rgba(16, 185, 129, .08), transparent 60%),
            radial-gradient(900px 180px at 100% 0%, rgba(14, 165, 233, .08), transparent 60%);
    }

    .bundle-chip {
        transition: transform .18s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .bundle-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, .08);
    }

    .delivery-action-btn {
        transition: transform .14s ease, box-shadow .2s ease, background-color .2s ease, border-color .2s ease, color .2s ease;
    }

    .delivery-action-btn:hover {
        transform: translateY(-1px);
    }

    .delivery-action-btn:active {
        transform: translateY(1px) scale(.985);
    }

    .product-tile {
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .product-tile:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 24px rgba(15, 23, 42, .12);
    }

    .product-image {
        transition: transform .35s ease;
    }

    .product-tile:hover .product-image {
        transform: scale(1.05);
    }

    .product-hidden-panel {
        overflow: hidden;
        transition: max-height .28s ease, opacity .22s ease, margin-top .22s ease;
    }

    .checkout-stat {
        transition: transform .18s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .checkout-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 18px rgba(15, 23, 42, .08);
    }

    .checkout-panel {
        transition: all .2s ease;
    }

    @keyframes catalogExpand {
        from { opacity: 0; transform: translateY(-8px) scaleY(.98); }
        to { opacity: 1; transform: translateY(0) scaleY(1); }
    }

    @keyframes cartBump {
        0% { transform: scale(1); }
        40% { transform: scale(1.09); }
        100% { transform: scale(1); }
    }

    .cart-bump {
        animation: cartBump .28s ease;
    }

    .cart-fly {
        position: fixed;
        width: 44px;
        height: 44px;
        border-radius: 9999px;
        object-fit: cover;
        pointer-events: none;
        z-index: 120;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .35);
        transition: transform .36s cubic-bezier(.2,.8,.2,1), opacity .36s ease;
    }
</style>

<div x-data="deliveryCategoryPage({
        slides: @js(data_get($deliveryShowcase ?? [], 'slides', [])),
        cards: @js(data_get($deliveryShowcase ?? [], 'cards', [])),
        deliveryServiceId: @js($deliveryServiceId ?? null),
        slotsEndpoint: @js(url('/api/v1/public/slots')),
        orderEndpoint: @js(url('/api/v1/public/orders')),
        customerPrefill: @js(auth()->check() ? [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'city' => 'Narvik',
        ] : null)
    })"
    x-init="init()"
    class="min-h-screen bg-slate-50"
>
    <section class="relative overflow-hidden bg-slate-900 text-white">
        <div class="delivery-hero-bg absolute inset-0 bg-cover bg-center transition-all duration-500"
             :style="`background-image: linear-gradient(rgba(15,23,42,.65), rgba(15,23,42,.8)), url('${currentSlideData.image || ''}')`"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-slate-50/10"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <p class="text-sm uppercase tracking-[0.22em] text-emerald-300">GLF BIKUBE</p>
            <h1 class="mt-4 max-w-4xl text-3xl font-black leading-tight sm:text-4xl lg:text-5xl" x-text="currentSlideData.title || 'Express Delivery Service'">
            </h1>
            <p class="mt-4 max-w-3xl text-base text-slate-200 sm:text-lg" x-text="currentSlideData.description || 'Same-day delivery of groceries, furniture, and meals across Narvik'"></p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ url('/lk/orders') }}"
                   class="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-400">
                    Мои заказы
                </a>
                <button type="button"
                        @click="document.getElementById('delivery-order-block')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                        class="rounded-xl border border-emerald-300 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                    Сделать новый заказ
                </button>
            </div>

            <div class="mt-8 flex items-center gap-2" x-show="slides.length > 1" x-cloak>
                <button type="button"
                        class="rounded-full border border-white/30 bg-white/10 p-2 hover:bg-white/20"
                        @click="prevSlide()"
                        aria-label="Предыдущий слайд">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="(slide, idx) in slides" :key="idx">
                    <button type="button"
                            @click="activeSlide = idx"
                            class="h-2.5 w-6 rounded-full transition"
                            :class="idx === activeSlide ? 'bg-emerald-300' : 'bg-white/40 hover:bg-white/60'"
                            aria-label="Переключить слайд"></button>
                </template>
                <button type="button"
                        class="rounded-full border border-white/30 bg-white/10 p-2 hover:bg-white/20"
                        @click="nextSlide()"
                        aria-label="Следующий слайд">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Choose Delivery Type</h2>
                <p class="mt-1 text-sm text-slate-600">Select a card below to open its catalog and apply smart filters for your scenario.</p>
            </div>
            <button type="button"
                    x-ref="cartBadge"
                    @click="cartOpen = !cartOpen; document.getElementById('delivery-order-block')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                    class="delivery-action-btn rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100"
                    :class="cartPulse ? 'cart-bump' : ''">
                Cart: <span x-text="cartCount"></span>
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <template x-for="card in cards" :key="card.key">
                <button type="button"
                        @click="selectCard(card.key)"
                        class="delivery-card group overflow-hidden rounded-2xl border bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="activeCardKey === card.key ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-200'">
                    <div class="relative h-36 overflow-hidden bg-slate-200">
                        <img :src="card.banner || card.image" :alt="cardDisplayTitle(card)" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 to-transparent"></div>
                        <p class="absolute bottom-3 left-3 text-sm font-semibold text-white" x-text="card.subtitle || cardDisplaySubtitle(card)"></p>
                    </div>
                    <div class="p-4">
                        <h2 class="text-lg font-bold text-slate-900" x-text="cardDisplayTitle(card)"></h2>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600" x-text="card.description || cardDisplayDescription(card)"></p>
                    </div>
                </button>
            </template>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8" x-show="activeCard" x-cloak>
        <div class="catalog-expand rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
             :class="activeCardKey === 'small' ? 'small-catalog-highlight' : ''">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-xl font-black text-slate-900" x-text="activeCard ? cardDisplayTitle(activeCard) : 'Delivery Services'"></h3>
                    <p class="mt-1 text-sm text-slate-600" x-text="activeCard ? (activeCard.subtitle || cardDisplaySubtitle(activeCard)) : ''"></p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" x-text="`${filteredItems.length} товаров`"></span>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <label class="text-sm font-medium text-slate-700 md:col-span-2 xl:col-span-2">
                    Search
                    <input x-model="search" type="text" placeholder="Product, store, keyword"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Store / Restaurant
                    <select x-model="vendor" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <template x-for="name in vendorOptions" :key="name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700" x-show="activeCardKey === 'food'" x-cloak>
                    Cuisine
                    <select x-model="cuisine" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <template x-for="name in cuisineOptions" :key="name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Tag
                    <select x-model="tag" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <template x-for="name in tagOptions" :key="name">
                            <option :value="name" x-text="name"></option>
                        </template>
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Sort by
                    <select x-model="sortBy" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="popular">Most relevant</option>
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                        <option value="prep_asc" x-show="activeCardKey === 'food'">Prep time: fastest</option>
                        <option value="name">Name A-Z</option>
                    </select>
                </label>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Min price: <span class="text-slate-900" x-text="formatMoney(priceMin)"></span>
                    <input type="range" :min="priceFloor" :max="priceCeil" x-model.number="priceMin" @input="syncPriceRange('min')" class="mt-2 w-full" />
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Max price: <span class="text-slate-900" x-text="formatMoney(priceMax)"></span>
                    <input type="range" :min="priceFloor" :max="priceCeil" x-model.number="priceMax" @input="syncPriceRange('max')" class="mt-2 w-full" />
                </label>
            </div>

            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" @click="resetFilters()" class="delivery-action-btn rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Reset filters</button>
                <span class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" x-text="`${filteredItems.length} items`"></span>
            </div>

            
            <div class="mt-2 rounded-xl border border-slate-200 bg-white/80 p-3" x-show="activeCardKey === 'small'" x-cloak>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick controls:</span>
                    <button type="button"
                            @click="sortBy = 'price_asc'"
                            class="delivery-action-btn rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        Budget first
                    </button>
                    <button type="button"
                            @click="sortBy = 'name'"
                            class="delivery-action-btn rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        Name A-Z
                    </button>
                    <button type="button"
                            @click="shuffleSmallItems()"
                            class="delivery-action-btn rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                        Surprise me
                    </button>
                    <button type="button"
                            @click="search = ''; vendor = ''; tag = ''; sortBy = 'popular'; refreshPriceBounds();"
                            class="delivery-action-btn rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        Clear all
                    </button>
                </div>
            </div>`r`n`r`n            <div class="mt-3 flex flex-wrap items-center gap-2" x-show="tagOptions.length" x-cloak>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick tags:</span>
                <template x-for="quickTag in tagOptions.slice(0, 8)" :key="`quick-${quickTag}`">
                    <button type="button"
                            @click="tag = (tag === quickTag ? '' : quickTag)"
                            class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                            :class="tag === quickTag ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-100'"
                            x-text="quickTag">
                    </button>
                </template>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2" x-show="activeCardKey === 'food' && cuisineOptions.length" x-cloak>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cuisines:</span>
                <template x-for="quickCuisine in cuisineOptions.slice(0, 6)" :key="`cuisine-${quickCuisine}`">
                    <button type="button"
                            @click="cuisine = (cuisine === quickCuisine ? '' : quickCuisine)"
                            class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                            :class="cuisine === quickCuisine ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-100'"
                            x-text="quickCuisine">
                    </button>
                </template>
            </div>

            

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="item in filteredItems" :key="`${activeCardKey}-${item.id || item.name}`">
                    <article data-product-card class="product-tile group overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition"
                             :class="activeCardKey === 'small' ? 'hover:border-emerald-300 hover:shadow-md' : ''">
                        <div class="h-40 overflow-hidden bg-slate-200">
                            <img :src="item.image" :alt="item.name" class="product-image h-full w-full object-cover" loading="lazy">
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700" x-text="item.vendor || 'Партнёр'"></p>
                            <h4 class="mt-1 text-base font-bold text-slate-900" x-text="item.name"></h4>
                            <p class="mt-2 min-h-[2.5rem] text-sm text-slate-600" x-text="item.description || '—'"></p>
                            <div class="mt-3 flex flex-wrap gap-1" x-show="Array.isArray(item.tags) && item.tags.length">
                                <template x-for="itemTag in (item.tags || [])" :key="`${item.id || item.name}-${itemTag}`">
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700" x-text="itemTag"></span>
                                </template>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1" x-show="activeCardKey === 'small'">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">Fresh today</span>
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">ETA 35-60 min</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1" x-show="activeCardKey === 'food'">
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700"
                                      x-show="item.cuisine"
                                      x-text="item.cuisine">
                                </span>
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700"
                                      x-show="item.prep_min"
                                      x-text="`~${item.prep_min} min`">
                                </span>
                            </div>
                            <div class="product-hidden-panel" :class="itemIsExpanded(item) ? 'mt-3 max-h-40 opacity-100' : 'max-h-0 opacity-0'">
                                <div class="rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-600">
                                    <p><span class="font-semibold text-slate-700">Supplier:</span> <span x-text="item.vendor || 'Unknown'"></span></p>
                                    <p><span class="font-semibold text-slate-700">Delivery:</span> Express in Narvik within selected slot.</p>
                                    <p x-show="item.cuisine"><span class="font-semibold text-slate-700">Cuisine:</span> <span x-text="item.cuisine"></span></p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="toggleItemDetails(item)"
                                    class="delivery-action-btn mt-2 text-xs font-semibold text-emerald-700 hover:text-emerald-600">
                                <span x-text="itemIsExpanded(item) ? 'Скрыть детали' : 'Подробнее о товаре'"></span>
                            </button>
                            <div class="mt-4 flex items-center justify-between gap-2">
                                <span class="text-lg font-black text-slate-900" x-text="formatMoney(item.price)"></span>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="addToCart(item, $event)"
                                            class="delivery-action-btn rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">
                                        В корзину
                                    </button>
                                    <button type="button"
                                            x-show="activeCardKey === 'small'"
                                            x-cloak
                                            @click="quickAddTwo(item, $event)"
                                            class="delivery-action-btn rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        +2
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <p class="mt-4 text-sm text-slate-500" x-show="filteredItems.length === 0" x-cloak>
                Товары не найдены. Измените фильтры.
            </p>
        </div>
    </section>

    <button type="button"
            @click="orderPanelOpen = true; cartOpen = true; document.getElementById('delivery-order-block')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
            class="delivery-action-btn fixed bottom-5 right-5 z-40 rounded-full bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-xl transition hover:bg-emerald-500"
            :class="cartPulse ? 'cart-bump' : ''">
        Cart (<span x-text="cartCount"></span>)
    </button>

    <section id="delivery-order-block" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-xl font-black text-slate-900">Оформление заказа</h3>
                    <p class="text-sm text-slate-600">Заказ отправляется в админ-панель и в личный кабинет клиента с полной детализацией.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="cartOpen = !cartOpen"
                            class="delivery-action-btn rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Корзина: <span x-text="cart.length"></span>
                    </button>
                    <button type="button"
                            @click="orderPanelOpen = !orderPanelOpen"
                            class="delivery-action-btn rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                        <span x-text="orderPanelOpen ? 'Свернуть оформление' : 'Развернуть оформление'"></span>
                    </button>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                <div class="checkout-stat rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Items in cart</p>
                    <p class="mt-1 text-lg font-black text-slate-900" x-text="cartCount"></p>
                </div>
                <div class="checkout-stat rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Delivery slot</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="selectedSlotLabel || 'Select date and slot'"></p>
                </div>
                <div class="checkout-stat rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Delivery note</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-900" x-text="deliveryPromise"></p>
                </div>
            </div>

            <div class="mt-4 checkout-panel" x-show="cartOpen" x-cloak x-transition.opacity.duration.180ms>
                <template x-if="cart.length === 0">
                    <p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Корзина пока пуста. Добавьте товары из каталога выше.</p>
                </template>
                <div class="space-y-2" x-show="cart.length > 0">
                    <div class="flex justify-end">
                        <button type="button"
                                @click="clearCart()"
                                class="delivery-action-btn rounded-lg border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                            Очистить корзину
                        </button>
                    </div>
                    <template x-for="row in cart" :key="row.cartKey">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900" x-text="row.name"></p>
                                <p class="text-xs text-slate-500" x-text="row.vendor"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="delivery-action-btn rounded border px-2 py-1 text-xs" @click="decrease(row.cartKey)">-</button>
                                <span class="w-8 text-center text-sm font-semibold" x-text="row.qty"></span>
                                <button type="button" class="delivery-action-btn rounded border px-2 py-1 text-xs" @click="increase(row.cartKey)">+</button>
                                <span class="ml-2 w-24 text-right text-sm font-bold" x-text="formatMoney((row.price || 0) * row.qty)"></span>
                                <button type="button" class="delivery-action-btn rounded border border-rose-200 px-2 py-1 text-xs text-rose-700" @click="remove(row.cartKey)">x</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="orderPanelOpen" x-cloak x-transition.opacity.duration.180ms class="mt-6 checkout-panel">
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3" x-show="hasAccountPrefill" x-cloak>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Заполнение данных</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button"
                                @click="setCustomerMode('auto')"
                                class="delivery-action-btn rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                :class="customerMode === 'auto' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-100'">
                            Авто из аккаунта
                        </button>
                        <button type="button"
                                @click="setCustomerMode('manual')"
                                class="delivery-action-btn rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                :class="customerMode === 'manual' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-100'">
                            Ручной ввод
                        </button>
                    </div>
                </div>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="placeOrder()">
                    <label class="text-sm font-medium text-slate-700">
                        Имя
                        <input x-model="form.customer_name" :readonly="customerMode === 'auto'" autocomplete="name" placeholder="Иван Иванов" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Email
                        <input x-model="form.customer_email" :readonly="customerMode === 'auto'" autocomplete="email" type="email" placeholder="name@example.com" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Телефон
                        <input x-model="form.customer_phone" :readonly="customerMode === 'auto'" autocomplete="tel" placeholder="+47 ..." required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Город
                        <input x-model="form.city" autocomplete="address-level2" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                        Адрес
                        <input x-model="form.street" autocomplete="street-address" placeholder="Улица, дом, подъезд" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Почтовый индекс
                        <input x-model="form.postal_code" autocomplete="postal-code" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Дата доставки
                        <input x-model="deliveryDate" type="date" required @change="loadSlots()" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    </label>
                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                        Delivery slot
                        <select x-model="selectedSlotId" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" :disabled="slotsLoading">
                            <option value="">Select slot</option>
                            <template x-for="slot in slots" :key="slot.id">
                                <option :value="slot.id" x-text="`${slot.name} (${slot.from || slot.start || '--'} - ${slot.to || slot.end || '--'})`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-500" x-show="slotsLoading" x-cloak>Loading available slots...</p>
                        <p class="mt-1 text-xs text-rose-600" x-show="!slotsLoading && slots.length === 0" x-cloak>No slots available for selected date.</p>
                        <p class="mt-1 text-xs text-emerald-700" x-show="selectedSlotLabel" x-cloak x-text="deliveryPromise"></p>
                    </label>

                    <fieldset class="rounded-xl border border-slate-200 p-3 sm:col-span-2">
                        <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment method</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="radio" x-model="form.payment_provider" value="stripe"> Stripe (card)
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="radio" x-model="form.payment_provider" value="vipps"> Vipps
                            </label>
                        </div>
                    </fieldset>

                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                        Notes
                        <textarea x-model="form.notes" rows="3" placeholder="Comment for courier, entrance code, floor..." class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
                    </label>

                    <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                        <div>
                            <p class="text-sm text-slate-500">Итого</p>
                            <p class="text-xl font-black text-slate-900" x-text="formatMoney(cartTotal)"></p>
                        </div>
                        <div class="text-right">
                            <p class="mb-2 text-xs font-medium text-slate-500" x-text="submissionHint"></p>
                            <button type="submit"
                                    :disabled="placing || !!submissionIssue"
                                    class="delivery-action-btn rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <span x-show="!placing">Оформить заказ</span>
                                <span x-show="placing" x-cloak>Создаём заказ...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mt-4 rounded-xl border px-4 py-3 text-sm"
                 x-show="message"
                 x-cloak
                 :class="messageOk ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
                 x-text="message">
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" x-show="lastOrder.order_number" x-cloak>
                <p class="font-semibold text-slate-900">Order number: <span x-text="lastOrder.order_number"></span></p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" @click="trackLastOrder()" class="delivery-action-btn rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Check status</button>
                    <a x-show="lastOrder.payment_url" x-cloak :href="lastOrder.payment_url" target="_blank" class="delivery-action-btn rounded-lg bg-slate-900 px-3 py-1 text-xs font-semibold text-white">Pay now</a>
                </div>
            </div>
        </div>
@if(isset($recentOrders) && $recentOrders->count())
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Последние заказы</h3>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($recentOrders->take(6) as $order)
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-sm font-semibold text-slate-900">#{{ $order->order_number ?? $order->id }}</p>
                            <p class="text-xs text-slate-500">Статус: {{ $order->tracking_status ?? $order->status ?? 'pending' }}</p>
                            <p class="text-xs text-slate-500">{{ optional($order->created_at)->format('d.m.Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>

<script>
function deliveryCategoryPage(config) {
    const defaultFoodCatalog = [
        { id: 'food-local-1', name: 'Pepperoni Pizza', vendor: 'Pizza Bakeren Narvik', price: 189.00, description: 'Classic pepperoni, medium size.', image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=80', tags: ['pizza'], cuisine: 'Italian', prep_min: 22 },
        { id: 'food-local-2', name: 'Margherita Pizza', vendor: 'Pizza Bakeren Narvik', price: 159.00, description: 'Tomato, mozzarella, basil.', image: 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=900&q=80', tags: ['pizza', 'vegetarian'], cuisine: 'Italian', prep_min: 18 },
        { id: 'food-local-3', name: 'Sushi Mix (12 pcs)', vendor: 'Narvik Sushi & Grill', price: 189.00, description: 'Chef selection sushi set.', image: 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=900&q=80', tags: ['sushi'], cuisine: 'Japanese', prep_min: 20 },
        { id: 'food-local-4', name: 'Chicken Wok', vendor: 'Asia House Narvik', price: 179.00, description: 'Hot wok with vegetables and rice.', image: 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=80', tags: ['asian', 'wok'], cuisine: 'Asian', prep_min: 24 },
        { id: 'food-local-5', name: 'Salmon Bowl', vendor: 'Narvik Sushi & Grill', price: 169.00, description: 'Fresh salmon, rice, avocado.', image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=900&q=80', tags: ['bowl', 'healthy'], cuisine: 'Japanese', prep_min: 16 },
        { id: 'food-local-6', name: 'Burger menu', vendor: 'Tind Restaurant Narvik', price: 219.00, description: 'Burger + fries + drink.', image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80', tags: ['burger'], cuisine: 'American', prep_min: 19 },
    ];

    const fallbackSlides = [
        {
            title: 'Delivery in Narvik: groceries, freight, and restaurant food in one flow',
            subtitle: 'GLF BiKuBe',
            description: 'Choose a delivery type, add items with animation, and complete checkout in a few steps.',
            image: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=80'
        }
    ];

    const fallbackCards = [
        { key: 'small', title: 'Products and Small Items', subtitle: 'From Bunnpris, REMA 1000 and more', description: 'Daily essentials and small goods from local stores.', banner: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=80', items: [] },
        { key: 'freight', title: 'Heavy Freight', subtitle: 'Furniture, appliances, building materials', description: 'Large-item pickup and delivery with proper handling.', banner: 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1200&q=80', items: [] },
        { key: 'food', title: 'Restaurant Food', subtitle: 'Pizza Bakeren, sushi, and more', description: 'Fast delivery from restaurants and cafes in the city.', banner: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80', items: [] }
    ];

    const normalizeItem = (item = {}) => ({
        ...item,
        id: item.id ?? item.name,
        name: item.name || 'Product',
        vendor: item.vendor || '',
        description: item.description || '',
        image: item.image || '',
        price: Number(item.price) || 0,
        tags: Array.isArray(item.tags) ? item.tags : [],
        cuisine: item.cuisine || '',
        prep_min: Number(item.prep_min) || null,
    });

    const normalizeCards = (cards) => {
        const list = Array.isArray(cards) ? [...cards] : [];

        if (!list.length) {
            return fallbackCards.map((card) => ({ ...card, items: (card.items || []).map(normalizeItem) }));
        }

        return list.map((card) => {
            const incomingItems = Array.isArray(card?.items) ? card.items : [];

            if (card?.key === 'food') {
                const byId = new Map();
                [...incomingItems, ...defaultFoodCatalog].forEach((rawItem) => {
                    const normalized = normalizeItem(rawItem);
                    const key = String(normalized.id || normalized.name);
                    if (!byId.has(key)) {
                        byId.set(key, normalized);
                    }
                });

                return { ...card, items: Array.from(byId.values()) };
            }

            return { ...card, items: incomingItems.map(normalizeItem) };
        });
    };

    const normalizeSlot = (slot = {}) => ({
        ...slot,
        id: slot.id ?? slot.slot_id ?? '',
        name: slot.name || slot.label || 'Slot',
        from: slot.from || slot.start || slot.window_start || '--',
        to: slot.to || slot.end || slot.window_end || '--',
    });

    return {
        slides: Array.isArray(config.slides) && config.slides.length ? config.slides : fallbackSlides,
        cards: normalizeCards(Array.isArray(config.cards) && config.cards.length ? config.cards : fallbackCards),
        activeSlide: 0,
        activeCardKey: null,
        search: '',
        vendor: '',
        cuisine: '',
        tag: '',
        sortBy: 'popular',
        priceFloor: 0,
        priceCeil: 5000,
        priceMin: 0,
        priceMax: 5000,
        cart: [],
        cartOpen: true,
        orderPanelOpen: true,
        cartPulse: false,
        cartPulseTimer: null,
        expandedItems: {},
        accountPrefill: config.customerPrefill || null,
        customerMode: (config.customerPrefill && (config.customerPrefill.name || config.customerPrefill.email || config.customerPrefill.phone)) ? 'auto' : 'manual',
        manualFormSnapshot: null,
        slots: [],
        slotsLoading: false,
        selectedSlotId: '',
        deliveryDate: new Date().toISOString().slice(0, 10),
        placing: false,
        message: '',
        messageOk: false,
        lastOrder: { order_number: '', payment_url: '' },
        form: {
            customer_name: '',
            customer_email: '',
            customer_phone: '',
            street: '',
            city: 'Narvik',
            postal_code: '',
            notes: '',
            payment_provider: 'stripe',
        },

        get currentSlideData() {
            return this.slides[this.activeSlide] || fallbackSlides[0];
        },

        get activeCard() {
            return this.cards.find((card) => card.key === this.activeCardKey) || this.cards[0] || null;
        },

        get items() {
            return Array.isArray(this.activeCard?.items) ? this.activeCard.items : [];
        },

        get vendorOptions() {
            return [...new Set(this.items.map((item) => (item.vendor || '').trim()).filter(Boolean))].sort();
        },

        get tagOptions() {
            return [...new Set(this.items.flatMap((item) => Array.isArray(item.tags) ? item.tags : []).filter(Boolean))].sort();
        },

        get cuisineOptions() {
            return [...new Set(this.items.map((item) => (item.cuisine || '').trim()).filter(Boolean))].sort();
        },

        get filteredItems() {
            const q = this.search.trim().toLowerCase();

            const filtered = this.items.filter((item) => {
                const bySearch = !q || [item.name, item.vendor, item.description]
                    .filter(Boolean)
                    .some((part) => String(part).toLowerCase().includes(q));

                const byVendor = !this.vendor || item.vendor === this.vendor;
                const byCuisine = !this.cuisine || item.cuisine === this.cuisine;
                const byTag = !this.tag || (Array.isArray(item.tags) && item.tags.includes(this.tag));
                const price = Number(item.price) || 0;
                const byPrice = price >= this.priceMin && price <= this.priceMax;

                return bySearch && byVendor && byCuisine && byTag && byPrice;
            });

            if (this.sortBy === 'price_asc') return filtered.sort((a, b) => (Number(a.price) || 0) - (Number(b.price) || 0));
            if (this.sortBy === 'price_desc') return filtered.sort((a, b) => (Number(b.price) || 0) - (Number(a.price) || 0));
            if (this.sortBy === 'name') return filtered.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'ru'));
            if (this.sortBy === 'prep_asc') return filtered.sort((a, b) => (Number(a.prep_min) || 999) - (Number(b.prep_min) || 999));

            return filtered.sort((a, b) => (Array.isArray(b.tags) ? b.tags.length : 0) - (Array.isArray(a.tags) ? a.tags.length : 0));
        },

        get cartCount() {
            return this.cart.reduce((sum, row) => sum + (Number(row.qty) || 0), 0);
        },

        get cartTotal() {
            return this.cart.reduce((sum, row) => sum + ((Number(row.price) || 0) * row.qty), 0);
        },

        get hasAccountPrefill() {
            return !!(this.accountPrefill && (this.accountPrefill.name || this.accountPrefill.email || this.accountPrefill.phone));
        },

        get selectedSlot() {
            return this.slots.find((slot) => String(slot.id) === String(this.selectedSlotId)) || null;
        },

        get selectedSlotLabel() {
            const slot = this.selectedSlot;
            if (!slot) return '';
            return `${slot.name} (${slot.from} - ${slot.to})`;
        },

        get deliveryPromise() {
            if (!this.selectedSlotLabel) {
                return 'Выберите интервал доставки, чтобы увидеть точное окно прибытия курьера.';
            }

            const dateLabel = this.deliveryDate
                ? new Date(`${this.deliveryDate}T00:00:00`).toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' })
                : '';

            return `Доставка ${dateLabel ? `${dateLabel}, ` : ''}${this.selectedSlotLabel}. Курьер прибудет в выбранный интервал.`;
        },

        get submissionIssue() {
            if (!config.deliveryServiceId) return 'Delivery service is not configured in the system.';
            if (!this.cart.length) return 'Add items to your cart first.';
            if (!this.selectedSlotId) return 'Select a delivery slot.';
            if (!this.form.customer_name || !this.form.customer_email || !this.form.customer_phone) return 'Fill in customer contact details.';
            if (!this.form.street || !this.form.city || !this.form.postal_code) return 'Fill in delivery address fields.';
            return '';
        },

        get submissionHint() {
            if (this.placing) return 'Submitting order...';
            if (this.submissionIssue) return this.submissionIssue;
            return 'Everything is ready. Confirm order to send it to admin and customer dashboard.';
        },

        init() {
            this.selectCard(this.cards[0]?.key || null);
            this.manualFormSnapshot = { ...this.form };

            if (this.hasAccountPrefill && this.customerMode === 'auto') {
                this.applyAccountPrefill();
            }

            if (this.slides.length > 1) {
                setInterval(() => {
                    this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                }, 6000);
            }

            this.loadSlots();
        },

        selectCard(key) {
            this.activeCardKey = key;
            this.expandedItems = {};
            this.resetFilters();
            this.refreshPriceBounds();
        },

        refreshPriceBounds() {
            const prices = this.items.map((item) => Number(item.price) || 0).filter((value) => value > 0);
            if (!prices.length) {
                this.priceFloor = 0;
                this.priceCeil = 5000;
                this.priceMin = 0;
                this.priceMax = 5000;
                return;
            }

            this.priceFloor = Math.floor(Math.min(...prices));
            this.priceCeil = Math.ceil(Math.max(...prices));
            this.priceMin = this.priceFloor;
            this.priceMax = this.priceCeil;
        },

        syncPriceRange(edge) {
            if (edge === 'min' && this.priceMin > this.priceMax) this.priceMax = this.priceMin;
            if (edge === 'max' && this.priceMax < this.priceMin) this.priceMin = this.priceMax;
        },

        resetFilters() {
            this.search = '';
            this.vendor = '';
            this.cuisine = '';
            this.tag = '';
            this.sortBy = 'popular';
            this.refreshPriceBounds();
        },

        prevSlide() {
            if (!this.slides.length) return;
            this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length;
        },

        nextSlide() {
            if (!this.slides.length) return;
            this.activeSlide = (this.activeSlide + 1) % this.slides.length;
        },

        cardDisplayTitle(card) {
            const map = { small: 'Products and Small Items', freight: 'Heavy Freight', food: 'Restaurant Food' };
            return map[card?.key] || card?.title || 'Catalog';
        },

        cardDisplaySubtitle(card) {
            const map = { small: 'From Bunnpris, REMA 1000 and more', freight: 'Furniture, appliances, building materials', food: 'Pizza Bakeren, sushi, and more' };
            return map[card?.key] || card?.subtitle || '';
        },

        cardDisplayDescription(card) {
            const map = {
                small: 'Daily essentials and small goods from local stores.',
                freight: 'Large-item pickup and delivery with proper handling.',
                food: 'Fast delivery from restaurants and cafes in the city.'
            };
            return map[card?.key] || card?.description || '';
        },

        formatMoney(value) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'NOK', minimumFractionDigits: 2 }).format(Number(value) || 0);
        },

        itemKey(item) {
            return `${this.activeCardKey}:${item?.id || item?.name || 'item'}`;
        },

        itemIsExpanded(item) {
            return !!this.expandedItems[this.itemKey(item)];
        },

        toggleItemDetails(item) {
            const key = this.itemKey(item);
            this.expandedItems = {
                ...this.expandedItems,
                [key]: !this.expandedItems[key],
            };
        },

        shuffleSmallItems() {
            if (this.activeCardKey !== 'small') return;
            const index = this.cards.findIndex((card) => card.key === 'small');
            if (index < 0) return;

            const original = Array.isArray(this.cards[index].items) ? this.cards[index].items : [];
            if (original.length < 2) return;

            const shuffled = [...original];
            for (let i = shuffled.length - 1; i > 0; i -= 1) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }

            this.cards.splice(index, 1, { ...this.cards[index], items: shuffled });
            this.triggerCartPulse();
        },

        triggerCartPulse() {
            this.cartPulse = false;
            requestAnimationFrame(() => {
                this.cartPulse = true;
            });
            if (this.cartPulseTimer) {
                clearTimeout(this.cartPulseTimer);
            }
            this.cartPulseTimer = setTimeout(() => {
                this.cartPulse = false;
            }, 320);
        },

        applyAccountPrefill() {
            if (!this.hasAccountPrefill) return;
            this.form.customer_name = this.accountPrefill.name || this.form.customer_name;
            this.form.customer_email = this.accountPrefill.email || this.form.customer_email;
            this.form.customer_phone = this.accountPrefill.phone || this.form.customer_phone;
            this.form.city = this.accountPrefill.city || this.form.city;
        },

        setCustomerMode(mode) {
            if (!['auto', 'manual'].includes(mode)) return;

            if (mode === 'auto') {
                this.manualFormSnapshot = { ...this.form };
                this.customerMode = 'auto';
                this.applyAccountPrefill();
                return;
            }

            this.customerMode = 'manual';
            if (this.manualFormSnapshot) {
                this.form = { ...this.form, ...this.manualFormSnapshot };
            }
        },

        addToCart(item, event) {
            const cartKey = `${this.activeCardKey}:${item.id || item.name}`;
            const existing = this.cart.find((row) => row.cartKey === cartKey);

            if (existing) {
                existing.qty += 1;
            } else {
                this.cart.push({
                    cartKey,
                    id: item.id || item.name,
                    sectionKey: this.activeCardKey,
                    name: item.name || 'Item',
                    vendor: item.vendor || '',
                    vendor_id: item.vendor_id || this.activeCard?.fallback_vendor_id || null,
                    description: item.description || '',
                    image: item.image || this.activeCard?.banner || '',
                    price: Number(item.price) || 0,
                    qty: 1,
                    tags: Array.isArray(item.tags) ? item.tags : []
                });
            }

            this.animateAddToCart(event, item.image || this.activeCard?.banner || '');
            this.triggerCartPulse();
        },

        quickAddTwo(item, event) {
            this.addToCart(item, event);
            this.addToCart(item, event);
        },

        addFilteredToCart(limit = 5) {
            this.filteredItems.slice(0, Math.max(1, Number(limit) || 1)).forEach((item) => this.addToCart(item));
        },

        addSmallBundle(bundleKey) {
            if (this.activeCardKey !== 'small') return;

            const items = this.items || [];
            let selected = [];

            if (bundleKey === 'breakfast') {
                selected = items.filter((item) => (item.tags || []).some((tag) => ['bakery', 'fruit', 'protein', 'grocery'].includes(String(tag).toLowerCase()))).slice(0, 4);
            } else if (bundleKey === 'home') {
                selected = items.filter((item) => (item.tags || []).some((tag) => ['home', 'essentials'].includes(String(tag).toLowerCase()))).slice(0, 4);
            } else {
                selected = [...items].sort((a, b) => (Number(a.price) || 0) - (Number(b.price) || 0)).slice(0, 5);
            }

            selected.forEach((item) => this.addToCart(item));
        },

        animateAddToCart(event, image) {
            const target = this.$refs.cartBadge || this.$refs.cartBadgeInline;
            const source = event?.currentTarget;

            if (!target || !source || !image) {
                return;
            }

            const sourceRect = source.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const fly = document.createElement('img');
            fly.src = image;
            fly.className = 'cart-fly';
            fly.style.left = `${sourceRect.left + sourceRect.width / 2 - 22}px`;
            fly.style.top = `${sourceRect.top + sourceRect.height / 2 - 22}px`;
            fly.style.opacity = '1';
            document.body.appendChild(fly);

            requestAnimationFrame(() => {
                fly.style.transform = `translate(${targetRect.left - sourceRect.left}px, ${targetRect.top - sourceRect.top}px) scale(.25)`;
                fly.style.opacity = '.3';
            });

            setTimeout(() => fly.remove(), 460);
        },

        increase(cartKey) {
            const row = this.cart.find((item) => item.cartKey === cartKey);
            if (row) row.qty += 1;
        },

        decrease(cartKey) {
            const row = this.cart.find((item) => item.cartKey === cartKey);
            if (!row) return;
            row.qty -= 1;
            if (row.qty <= 0) this.remove(cartKey);
        },

        remove(cartKey) {
            this.cart = this.cart.filter((item) => item.cartKey !== cartKey);
        },

        clearCart() {
            this.cart = [];
            this.triggerCartPulse();
        },

        async loadSlots() {
            this.selectedSlotId = '';
            this.slotsLoading = true;
            this.message = '';

            if (!this.deliveryDate) {
                this.slots = [];
                this.slotsLoading = false;
                return;
            }

            try {
                const response = await fetch(`${config.slotsEndpoint}?date=${encodeURIComponent(this.deliveryDate)}`);
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || payload?.success === false) {
                    throw new Error(payload?.message || 'Failed to load available delivery slots.');
                }

                this.slots = Array.isArray(payload?.data) ? payload.data.map(normalizeSlot) : [];

                if (this.slots.length) {
                    this.selectedSlotId = String(this.slots[0].id);
                }
            } catch (_error) {
                this.slots = [];
                this.messageOk = false;
                this.message = 'Не удалось загрузить интервалы доставки. Попробуйте снова.';
            } finally {
                this.slotsLoading = false;
            }
        },

        validateBeforeSubmit() {
            return this.submissionIssue || '';
        },

        buildPayload() {
            const items = this.cart.map((row) => ({
                service_id: config.deliveryServiceId,
                quantity: row.qty,
                notes: `${row.name} | ${row.vendor}`,
                title: row.name,
                description: row.description,
                unit_price: row.price,
                image_url: row.image,
                product_id: row.id,
                store_id: row.vendor_id ? String(row.vendor_id) : null,
                metadata: {
                    source: 'category.delivery',
                    section: row.sectionKey,
                    tags: row.tags,
                    vendor: row.vendor,
                }
            }));

            return {
                customer: {
                    name: this.form.customer_name,
                    email: this.form.customer_email,
                    phone: this.form.customer_phone,
                },
                address: {
                    street: this.form.street,
                    city: this.form.city,
                    postal_code: this.form.postal_code,
                    lat: 68.4385,
                    lng: 17.4273,
                },
                slot_id: this.selectedSlotId,
                payment_provider: this.form.payment_provider,
                notes: this.form.notes,
                items,
            };
        },

        async placeOrder() {
            this.message = '';
            this.messageOk = false;

            const issue = this.validateBeforeSubmit();
            if (issue) {
                this.message = issue;
                return;
            }

            this.placing = true;

            try {
                const response = await fetch(config.orderEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.buildPayload()),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data?.success) {
                    const fieldErrors = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
                    throw new Error([data?.message, fieldErrors].filter(Boolean).join(' ') || 'Failed to place order.');
                }

                const orderNumber = data?.data?.order_number || '';
                this.messageOk = true;
                this.message = `Order ${orderNumber} created successfully. ${this.deliveryPromise}`;
                this.lastOrder.order_number = orderNumber;
                this.lastOrder.payment_url = data?.data?.payment_url || '';
                this.cart = [];
                this.cartOpen = false;
                this.orderPanelOpen = false;
                this.triggerCartPulse();
            } catch (error) {
                this.message = error?.message || 'Failed to place order.';
            } finally {
                this.placing = false;
            }
        },

        async trackLastOrder() {
            if (!this.lastOrder.order_number) return;

            try {
                const response = await fetch(`${config.orderEndpoint}/${encodeURIComponent(this.lastOrder.order_number)}`);
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload?.success) {
                    throw new Error('Failed to fetch order status.');
                }

                this.messageOk = true;
                this.message = `Current status for ${this.lastOrder.order_number}: ${payload?.data?.status || 'unknown'}`;
            } catch (error) {
                this.messageOk = false;
                this.message = error?.message || 'Failed to fetch order status.';
            }
        }
    };
}
</script>
@endsection
