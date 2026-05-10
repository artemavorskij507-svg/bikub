<?php $__env->startSection('title', 'BiKuBe Delivery Narvik'); ?>

<?php $__env->startSection('content'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Archivo+Black&display=swap" rel="stylesheet">
<style>
    :root {
        --page-bg: #f3f5f6;
        --ink: #09172b;
        --muted: #5f6c80;
    }

    [x-cloak] { display: none !important; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

    .delivery-page { font-family: 'Manrope', sans-serif; background: var(--page-bg); color: var(--ink); }
    .display-face { font-family: 'Archivo Black', 'Manrope', sans-serif; }

    .lift {
        transition: transform .28s ease, box-shadow .28s ease;
    }

    .lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 30px rgba(2, 6, 23, 0.14);
    }

    .hero-sheen::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 82% 10%, rgba(194, 252, 122, 0.27), transparent 38%);
        pointer-events: none;
    }

    .mobile-checkout-bar {
        box-shadow: 0 14px 32px rgba(2, 6, 23, 0.24);
    }
</style>

<div x-data="deliveryPage()" x-init="init()" @keydown.escape.window="onEscape()" x-cloak class="delivery-page min-h-screen pb-20">
    <header class="sticky top-0 z-40 border-b border-white/60 bg-white/82 backdrop-blur-xl">
        <div class="mx-auto flex w-full max-w-[96rem] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="/" class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-sm font-black text-white shadow-lg shadow-emerald-500/35">B</span>
                <div class="min-w-0">
                    <p class="truncate text-[1.75rem] leading-none font-black tracking-tight text-slate-900">BiKuBe Delivery</p>
                    <p class="mt-1 truncate text-xs font-bold uppercase tracking-[0.15em] text-slate-500">
                        <span x-text="meta.city || 'Narvik'"></span>,
                        <span x-text="meta.country || 'Norway'"></span>
                    </p>
                </div>
            </a>

            <div class="hidden items-center gap-2 lg:flex">
                <template x-for="metric in highlights" :key="metric.label">
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700" x-text="`${metric.label}: ${metric.value}`"></span>
                </template>
            </div>

            <button x-ref="openCartButton" @click="openCart()" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600">
                Cart
                <span x-show="cart.length > 0" x-text="cart.length" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-400 text-[10px] font-black text-slate-900"></span>
            </button>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[96rem] px-4 pb-10 pt-6 sm:px-6 lg:px-8">
        <section class="rounded-[2.3rem] bg-white p-4 shadow-sm lg:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    <button type="button" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Shop</button>
                    <button type="button" @click="activeTab = 'grocery'" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Fresh produce</button>
                    <button type="button" @click="activeTab = 'food'" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Meals</button>
                    <button type="button" @click="activeTab = 'freight'" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Cargo</button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="jumpToCatalog()" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-white transition hover:bg-emerald-600">Show all</button>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-5">
                <article class="hero-sheen relative overflow-hidden rounded-3xl p-5 text-white lg:col-span-3 lg:p-7" style="background:linear-gradient(130deg,#0f7e55 0%, #1ea56f 52%, #58c592 100%);">
                    <p class="pointer-events-none display-face absolute left-4 top-2 uppercase leading-[0.8]" style="font-size:clamp(4.8rem,12vw,11rem); color:rgba(166,242,99,0.95);">Fresh</p>
                    <div class="relative z-10 grid gap-4 lg:grid-cols-12 lg:items-end" style="margin-top:7.6rem;">
                        <div class="lg:col-span-7">
                            <span class="inline-flex rounded-full border border-white/40 bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-50">same-day delivery</span>
                            <p class="mt-4 max-w-[38ch] text-sm font-medium text-emerald-50 sm:text-base">Shop from real BiKuBe Narvik partners and deliver groceries, food, and essentials straight to your door.</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button @click="jumpToCatalog()" class="rounded-full bg-white px-5 py-2.5 text-sm font-black text-slate-900 transition hover:bg-emerald-100">Shop now</button>
                                <button @click="openCart()" class="rounded-full border border-white/40 bg-black/20 px-5 py-2.5 text-sm font-black text-white transition hover:bg-black/30">Open checkout</button>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/35 bg-white/15 p-3 backdrop-blur-sm lg:col-span-5">
                            <img :src="heroImage" alt="Narvik delivery hero" class="h-44 w-full rounded-xl object-cover shadow-xl shadow-black/20 sm:h-52" loading="eager" fetchpriority="high" decoding="async" width="720" height="420" />
                            <div class="mt-2 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-100">Primary zone</p>
                                    <p class="text-sm font-black text-white" x-text="primaryZoneName"></p>
                                </div>
                                <p class="text-xl font-black text-white" x-text="formatMoney((featuredCatalog[0] && featuredCatalog[0].price) || 0)"></p>
                            </div>
                        </div>
                    </div>
                </article>

                <aside class="space-y-3 lg:col-span-2">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Popular categories</h2>
                    <template x-for="cat in popularCategories" :key="`cat-${cat.key}`">
                        <article class="lift flex items-center gap-3 rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white px-3 py-3">
                            <img :src="cat.image" :alt="cat.label" class="h-14 w-14 rounded-xl object-cover" loading="lazy" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-black text-slate-900" x-text="cat.label"></p>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500" x-text="`${cat.count} products`"></p>
                            </div>
                        </article>
                    </template>
                </aside>
            </div>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-2">
            <article class="overflow-hidden rounded-3xl border border-sky-200" style="background:linear-gradient(135deg,#dff3ff 0%, #ebf8ff 60%, #ffffff 100%);">
                <div class="grid gap-4 p-6 lg:grid-cols-2 lg:p-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">Ready to fill your cart</p>
                        <h3 class="mt-3 text-5xl font-black leading-[0.95] text-slate-900">With Freshness?</h3>
                        <p class="mt-4 text-sm font-medium text-slate-600">Live catalog from Narvik stores and restaurants with transparent fee and ETA.</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <button @click="jumpToCatalog()" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-black text-white transition hover:bg-emerald-600">Open catalog</button>
                        <button @click="openCart()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-100">Checkout</button>
                        </div>
                    </div>
                    <div class="relative min-h-[17rem] overflow-hidden rounded-[1.6rem] bg-white/80 p-2">
                        <img :src="generatedImage('basket_blue') || heroImage" alt="Fresh basket BiKuBe" class="h-full w-full rounded-[1.1rem] object-cover" loading="lazy" />
                    </div>
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-4xl font-black leading-none tracking-tight text-slate-900">Just for you</h3>
                    <button @click="activeTab = 'all'" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-700 transition hover:bg-slate-100">Show all</button>
                </div>
                <div class="space-y-3">
                    <template x-for="card in featuredCatalog.slice(0, 5)" :key="`quick-${card.id}`">
                        <article class="lift flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <img :src="safeImage(card.image_url, card.section, card.id)" <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onImgError($event, card.section, card.id)" :alt="card.title" class="h-16 w-16 rounded-xl object-cover" loading="lazy" decoding="async" width="96" height="96" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-black text-slate-900" x-text="card.title"></p>
                                <p class="truncate text-xs font-semibold uppercase tracking-[0.08em] text-slate-500" x-text="card.store"></p>
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="text-base font-black text-slate-900" x-text="formatMoney(card.price)"></p>
                                    <button type="button" @click="addToCart(card)" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-emerald-600 hover:text-white hover:ring-emerald-600">Add to cart</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </article>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-3">
            <aside class="space-y-4">
                <article class="lift relative overflow-hidden rounded-3xl p-5 text-white shadow-lg shadow-emerald-300/30" style="background:linear-gradient(140deg,#16a34a 0%, #0f766e 100%);">
                    <img :src="safeImage(generatedImage('promo_green'), 'grocery', 7001)" <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onImgError($event, 'grocery', 7001)" alt="BiKuBe promo green" class="absolute inset-0 h-full w-full object-cover opacity-35" loading="lazy" decoding="async" width="640" height="420" />
                    <div class="relative">
                        <p class="text-xs font-black uppercase tracking-[0.15em] text-emerald-100">New where? Enjoy 10% off</p>
                        <p class="mt-2 text-sm font-semibold text-emerald-50">Sign up and get instant savings on your first grocery purchase.</p>
                    </div>
                </article>
                <article class="lift relative overflow-hidden rounded-3xl p-5 text-white shadow-lg shadow-pink-300/30" style="background:linear-gradient(140deg,#ec4899 0%, #e11d48 100%);">
                    <img :src="safeImage(generatedImage('promo_pink'), 'food', 7002)" <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onImgError($event, 'food', 7002)" alt="BiKuBe promo pink" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" decoding="async" width="640" height="420" />
                    <div class="relative">
                        <p class="text-xs font-black uppercase tracking-[0.15em] text-pink-100">Free delivery over 550 NOK</p>
                        <p class="mt-2 text-sm font-semibold text-pink-50">Stock up weekly groceries and save more with zero delivery charges.</p>
                    </div>
                </article>
                <article class="lift relative overflow-hidden rounded-3xl p-5 text-slate-900 shadow-lg shadow-amber-200/50" style="background:linear-gradient(140deg,#fde047 0%, #f59e0b 100%);">
                    <img :src="safeImage(generatedImage('promo_yellow'), 'grocery', 7003)" <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onImgError($event, 'grocery', 7003)" alt="BiKuBe promo yellow" class="absolute inset-0 h-full w-full object-cover opacity-22" loading="lazy" decoding="async" width="640" height="420" />
                    <div class="relative">
                        <p class="text-xs font-black uppercase tracking-[0.15em] text-amber-900/90">Fresh groceries for your family</p>
                        <p class="mt-2 text-sm font-semibold text-amber-900/85">Weekly essentials and healthy picks from local Narvik partners.</p>
                    </div>
                </article>
            </aside>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.15em] text-slate-500">Weekly best selling items</p>
                        <h2 class="text-4xl font-black tracking-tight text-slate-900">Most selling products</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="chip in quickChips" :key="`chip-${chip.key}`">
                            <button type="button" @click="activeTab = chip.key" :class="activeTab === chip.key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] transition" x-text="chip.label"></button>
                        </template>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <template x-for="card in featuredCatalog" :key="`featured-grid-${card.id}`">
                        <article class="lift overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <img :src="card.image_url || fallbackImage(card.section, card.id)" :alt="card.title" class="h-36 w-full rounded-xl object-cover" loading="lazy" />
                            <div class="mt-3">
                                <p class="truncate text-base font-black text-slate-900" x-text="card.title"></p>
                                <p class="truncate text-xs font-semibold uppercase tracking-[0.08em] text-slate-500" x-text="card.store"></p>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <p class="text-base font-black text-slate-900" x-text="formatMoney(card.price)"></p>
                                    <button type="button" @click="addToCart(card)" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200 transition hover:bg-emerald-600 hover:text-white hover:ring-emerald-600">Add to cart</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </article>
        </section>

        <section id="delivery-catalog" class="mt-8 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900">Today's fresh picks</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500">Real-time offers from active BiKuBe entities in Narvik.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="metric in highlights" :key="`metric-${metric.label}`">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.1em] text-slate-600" x-text="`${metric.label}: ${metric.value}`"></span>
                    </template>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <label class="relative">
                    <span class="sr-only">Search partners and services</span>
                    <input x-model.debounce.250ms="searchQuery" type="search" placeholder="Search products, stores and restaurants..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-10 text-sm font-medium text-slate-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                    <svg class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                </label>

                <label class="block md:max-w-[16rem]">
                    <span class="sr-only">Coverage zone</span>
                    <select x-model="selectedZoneId" @change="loadSlots()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                        <option value="">All zones</option>
                        <template x-for="zone in partnerList('zones')" :key="`zone-select-${zone.id}`">
                            <option :value="zone.id" x-text="zone.name"></option>
                        </template>
                    </select>
                </label>

                <button @click="openCart()" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-600">Checkout</button>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2" x-show="searchQuery || selectedZoneId || activeTab !== 'all'">
                <span class="text-xs font-bold uppercase tracking-[0.11em] text-slate-500">Active filters:</span>
                <span x-show="activeTab !== 'all'" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700" x-text="`Tab: ${activeTab}`"></span>
                <span x-show="selectedZoneId" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700" x-text="`Zone: ${primaryZoneName}`"></span>
                <span x-show="searchQuery" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700" x-text="`Search: ${searchQuery}`"></span>
                <button @click="clearFilters()" type="button" class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100">Clear</button>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="rounded-full px-4 py-2 text-sm font-bold transition">All <span x-text="tabCount('all')"></span></button>
                <button @click="activeTab = 'grocery'" :class="activeTab === 'grocery' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" class="rounded-full px-4 py-2 text-sm font-bold transition">Grocery <span x-text="tabCount('grocery')"></span></button>
                <button @click="activeTab = 'food'" :class="activeTab === 'food' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'" class="rounded-full px-4 py-2 text-sm font-bold transition">Food <span x-text="tabCount('food')"></span></button>
                <button @click="activeTab = 'freight'" :class="activeTab === 'freight' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'" class="rounded-full px-4 py-2 text-sm font-bold transition">Freight <span x-text="tabCount('freight')"></span></button>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <template x-for="card in visibleCatalog" :key="card.id">
                    <article class="lift group flex h-full flex-col overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                        <div class="relative h-44 overflow-hidden">
                            <img :src="safeImage(card.image_url, card.section, card.id)" <?php $__errorArgs = [];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>="onImgError($event, card.section, card.id)" :alt="card.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" width="520" height="320" />
                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-700" x-text="sectionLabel(card.section)"></span>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="text-base font-black text-slate-900" x-text="card.title"></h3>
                            <p class="mt-1 text-xs font-bold uppercase tracking-[0.11em] text-emerald-700" x-text="card.store"></p>
                            <p class="mt-2 text-sm text-slate-600" x-text="truncate(card.description, 94)"></p>
                            <div class="mt-3 flex items-center justify-between">
                                <p class="text-lg font-black text-slate-900" x-text="formatMoney(card.price)"></p>
                                <p class="text-xs font-semibold text-slate-500" x-text="extractEta(card)"></p>
                            </div>
                            <div class="mt-4">
                                <template x-if="getItemQty(card.id) === 0">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="addToCart(card)" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600">Add to cart</button>
                                        <button type="button" @click="buyNow(card)" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">Buy now</button>
                                    </div>
                                </template>
                                <template x-if="getItemQty(card.id) > 0">
                                    <div class="flex items-center justify-between rounded-xl bg-emerald-600 p-1 text-white shadow-lg shadow-emerald-500/30">
                                        <button type="button" @click="decreaseQty(card.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-700/70 font-black transition hover:bg-emerald-500">-</button>
                                        <span class="text-sm font-black" x-text="getItemQty(card.id)"></span>
                                        <button type="button" @click="increaseQty(card.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-700/70 font-black transition hover:bg-emerald-500">+</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div class="mt-5 text-center" x-show="canLoadMore">
                <button type="button" @click="loadMore()" class="rounded-full bg-slate-900 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600">
                    Load more offers
                </button>
            </div>

            <template x-if="filteredCatalog.length === 0">
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="text-base font-bold text-slate-700">No offers match your filter.</p>
                    <p class="mt-1 text-sm text-slate-500">Try another tab or search query.</p>
                </div>
            </template>
        </section>
    </main>

    <div x-show="cart.length > 0 && !cartOpen" class="mobile-checkout-bar fixed bottom-4 left-4 right-4 z-40 md:hidden">
        <button type="button" @click="openCart()" class="flex w-full items-center justify-between rounded-2xl bg-slate-900 px-4 py-3 text-left text-white">
            <span class="text-xs font-bold uppercase tracking-[0.1em]" x-text="`${cart.length} items in cart`"></span>
            <span class="text-sm font-black" x-text="`${formatMoney(cartTotal)} · Checkout`"></span>
        </button>
    </div>

    <div x-show="cartOpen" class="relative z-50" role="dialog" aria-modal="true">
        <div x-show="cartOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeCart()"></div>

        <div class="fixed inset-y-0 right-0 flex w-full max-w-2xl">
            <div x-show="cartOpen" x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="pointer-events-auto flex h-full w-full flex-col border-l border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <h2 class="text-xl font-black text-slate-900">Checkout (<span x-text="cart.length"></span>)</h2>
                    <button @click="closeCart()" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                        <span class="sr-only">Close drawer</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto bg-slate-50 p-6 scrollbar-hide">
                    <template x-if="resultMessage">
                        <div aria-live="polite" class="rounded-2xl border px-4 py-3 text-sm font-bold" :class="resultOk ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'" x-text="resultMessage"></div>
                    </template>

                    <section class="rounded-2xl border border-slate-200 bg-white p-4">
                        <h3 class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Items</h3>
                        <div class="mt-3 space-y-3">
                            <template x-if="cart.length === 0">
                                <p class="rounded-xl bg-slate-50 px-3 py-5 text-center text-sm font-semibold text-slate-500">Your cart is empty.</p>
                            </template>

                            <template x-for="item in cart" :key="`cart-${item.id}`">
                                <article class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <img :src="item.image_url || fallbackImage(item.section, item.id)" :alt="item.title" class="h-14 w-14 rounded-lg object-cover" loading="lazy" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-900" x-text="item.title"></p>
                                        <p class="truncate text-xs font-semibold uppercase tracking-[0.08em] text-emerald-700" x-text="item.store"></p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <button type="button" @click="decreaseQty(item.id)" class="inline-flex h-7 w-7 items-center justify-center rounded bg-slate-200 font-black text-slate-700 transition hover:bg-slate-300">-</button>
                                            <span class="text-xs font-black text-slate-900" x-text="item.qty"></span>
                                            <button type="button" @click="increaseQty(item.id)" class="inline-flex h-7 w-7 items-center justify-center rounded bg-slate-200 font-black text-slate-700 transition hover:bg-slate-300">+</button>
                                        </div>
                                    </div>
                                    <p class="text-sm font-black text-slate-900" x-text="formatMoney((item.price || 0) * item.qty)"></p>
                                </article>
                            </template>
                        </div>
                    </section>

                    <form @submit.prevent="submitOrder" @input.debounce.300ms="saveDraft()" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
                        <h3 class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Delivery details</h3>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="sm:col-span-2 text-xs font-bold text-slate-700">
                                Full name
                                <input x-ref="checkoutNameInput" x-model="form.customer.name" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                            <label class="text-xs font-bold text-slate-700">
                                Email
                                <input x-model="form.customer.email" type="email" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                            <label class="text-xs font-bold text-slate-700">
                                Phone
                                <input x-model="form.customer.phone" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="sm:col-span-2 text-xs font-bold text-slate-700">
                                Street address
                                <input x-model="form.address.street" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                            <label class="text-xs font-bold text-slate-700">
                                City
                                <input x-model="form.address.city" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                            <label class="text-xs font-bold text-slate-700">
                                Postal code
                                <input x-model="form.address.postal_code" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-xs font-bold text-slate-700">
                                Delivery date
                                <input x-model="deliveryDate" @change="loadSlots()" type="date" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100" />
                            </label>
                            <label class="text-xs font-bold text-slate-700">
                                Slot
                                <select x-model="selectedSlotId" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Select slot</option>
                                    <template x-for="slot in slots" :key="`checkout-slot-${slot.id}`">
                                        <option :value="slot.id" x-text="`${slot.name} (${slot.from} - ${slot.to})`"></option>
                                    </template>
                                </select>
                                <p x-show="slotLoading" class="mt-1 text-[11px] font-semibold text-slate-500">Loading delivery slots...</p>
                                <p x-show="slotError" class="mt-1 text-[11px] font-semibold text-rose-600" x-text="slotError"></p>
                                <p x-show="!slotLoading && !slotError && slots.length === 0" class="mt-1 text-[11px] font-semibold text-amber-700">No slots for this date/zone. Try another date or area.</p>
                            </label>
                        </div>

                        <label class="text-xs font-bold text-slate-700">
                            Notes
                            <textarea x-model="form.notes" rows="2" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100"></textarea>
                        </label>

                        <button type="submit" :disabled="submitting || cart.length === 0 || !selectedSlotId" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-300">
                            <span x-show="!submitting">Place order</span>
                            <span x-show="submitting">Processing...</span>
                        </button>
                    </form>
                </div>

                <div class="border-t border-slate-200 bg-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-500">Subtotal</p>
                        <p class="text-2xl font-black text-slate-900" x-text="formatMoney(cartTotal)"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function deliveryPage() {
        return {
            catalog: <?php echo json_encode(isset($deliveryCatalog) ? $deliveryCatalog : [], 15, 512) ?>,
            deliveryServiceId: <?php echo json_encode(isset($deliveryServiceId) ? $deliveryServiceId : null, 15, 512) ?>,
            highlights: <?php echo json_encode(isset($deliveryHighlights) ? $deliveryHighlights : [], 15, 512) ?>,
            partners: <?php echo json_encode(isset($deliveryPartners) ? $deliveryPartners : [], 15, 512) ?>,
            meta: <?php echo json_encode(isset($deliveryMeta) ? $deliveryMeta : [], 15, 512) ?>,
            activeTab: 'all',
            searchQuery: '',
            cart: [],
            cartOpen: false,
            slots: [],
            slotLoading: false,
            slotError: '',
            selectedSlotId: '',
            selectedZoneId: '',
            deliveryDate: new Date().toISOString().slice(0, 10),
            submitting: false,
            resultMessage: '',
            resultOk: false,
            visibleCount: 24,
            slotAbortController: null,
            checkoutDraftKey: 'bikube_delivery_checkout_v2',
            currencyFormatter: new Intl.NumberFormat('en-NO', { style: 'currency', currency: 'NOK' }),
            quickChips: [
                { key: 'grocery', label: 'Fresh Vegetables' },
                { key: 'food', label: 'Bakery & Food' },
                { key: 'freight', label: 'Freight & Cargo' },
                { key: 'all', label: 'All' },
            ],
            form: {
                customer: { name: '', email: '', phone: '' },
                address: {
                    street: '',
                    city: 'Narvik',
                    postal_code: '',
                    lat: 68.4385,
                    lng: 17.4273
                },
                notes: ''
            },

            get heroImage() {
                const generatedHero = this.generatedImage('hero');
                if (generatedHero) return generatedHero;
                const first = this.catalog[0];
                if (first && first.image_url) return first.image_url;
                return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=80';
            },
            get featuredCatalog() {
                const src = this.filteredCatalog.length ? this.filteredCatalog : this.catalog;
                return src.slice(0, 6);
            },
            get popularCategories() {
                return [
                    { key: 'grocery', label: 'Fresh Vegetables', image: this.fallbackImage('grocery', 11), count: this.tabCount('grocery') },
                    { key: 'food', label: 'Bakery & Food', image: this.fallbackImage('food', 22), count: this.tabCount('food') },
                    { key: 'freight', label: 'Freight & Cargo', image: this.fallbackImage('freight', 33), count: this.tabCount('freight') },
                    { key: 'all', label: 'All Services', image: this.fallbackImage('grocery', 44), count: this.tabCount('all') },
                ];
            },
            get primaryZoneName() {
                const zones = this.partnerList('zones');
                const selected = zones.find((z) => String(z.id) === String(this.selectedZoneId));
                return selected ? selected.name : (zones[0] ? zones[0].name : 'Narvik Central');
            },
            get formattedUpdatedAt() {
                if (!this.meta || !this.meta.generated_at) return 'just now';
                try {
                    return new Date(this.meta.generated_at).toLocaleTimeString('en-NO', { hour: '2-digit', minute: '2-digit' });
                } catch (error) {
                    return 'just now';
                }
            },
            get filteredCatalog() {
                const query = this.normalize(this.searchQuery);
                return this.catalog.filter((item) => {
                    const section = this.normalize(item.section || '');
                    if (this.activeTab !== 'all' && !this.sectionMatch(this.activeTab, section)) return false;
                    if (!query) return true;
                    const haystack = this.normalize([item.title, item.store, item.description, item.section].filter(Boolean).join(' '));
                    return haystack.includes(query);
                });
            },
            get cartTotal() {
                return this.cart.reduce((sum, row) => sum + ((Number(row.price) || 0) * row.qty), 0);
            },
            get visibleCatalog() {
                return this.filteredCatalog.slice(0, this.visibleCount);
            },
            get canLoadMore() {
                return this.filteredCatalog.length > this.visibleCount;
            },

            init() {
                this.hydrateDraft();
                this.selectedZoneId = this.meta && this.meta.default_zone_id ? String(this.meta.default_zone_id) : '';
                this.loadSlots();
                this.$watch('activeTab', () => { this.visibleCount = 24; });
                this.$watch('searchQuery', () => { this.visibleCount = 24; });
                this.$watch('selectedZoneId', () => { this.visibleCount = 24; });
                this.$watch('cartOpen', (isOpen) => {
                    if (!isOpen && this.$refs.openCartButton) {
                        this.$nextTick(() => this.$refs.openCartButton.focus());
                    }
                });
            },
            onEscape() {
                if (this.cartOpen) this.closeCart();
            },
            openCart() {
                this.cartOpen = true;
                this.$nextTick(() => {
                    if (this.$refs.checkoutNameInput) this.$refs.checkoutNameInput.focus();
                });
            },
            closeCart() {
                this.cartOpen = false;
            },
            jumpToCatalog() {
                const el = document.getElementById('delivery-catalog');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },
            clearFilters() {
                this.activeTab = 'all';
                this.searchQuery = '';
                this.selectedZoneId = '';
                this.visibleCount = 24;
                this.loadSlots();
            },
            loadMore() {
                this.visibleCount += 24;
            },
            tabCount(tab) {
                if (tab === 'all') return this.catalog.length;
                return this.catalog.filter((item) => this.sectionMatch(tab, this.normalize(item.section || ''))).length;
            },
            sectionMatch(tab, section) {
                if (tab === 'grocery') return ['grocery', 'retail', 'market', 'delivery'].includes(section);
                if (tab === 'food') return ['food', 'restaurant'].includes(section);
                if (tab === 'freight') return ['freight', 'moving', 'tow', 'roadside'].includes(section);
                return true;
            },
            sectionLabel(section) {
                const code = this.normalize(section);
                if (this.sectionMatch('grocery', code) && code !== 'food' && code !== 'restaurant') return 'Grocery';
                if (this.sectionMatch('food', code)) return 'Food';
                if (this.sectionMatch('freight', code)) return 'Freight';
                return 'Delivery';
            },
            partnerList(key) {
                if (!this.partners || typeof this.partners !== 'object') return [];
                const value = this.partners[key];
                return Array.isArray(value) ? value : [];
            },
            generatedImage(key) {
                if (!this.meta || typeof this.meta !== 'object') return '';
                const map = this.meta.generated_images || {};
                const value = map[key];
                return typeof value === 'string' ? value : '';
            },
            safeImage(primaryUrl, section, id = null) {
                if (typeof primaryUrl === 'string' && primaryUrl.trim() !== '') return primaryUrl;
                return this.fallbackImage(section, id);
            },
            onImgError(event, section, id = null) {
                if (!event || !event.target || event.target.dataset.fallbackApplied === '1') return;
                event.target.dataset.fallbackApplied = '1';
                event.target.src = this.fallbackImage(section, id);
            },
            normalize(value) {
                return String(value || '').trim().toLowerCase();
            },
            truncate(value, limit) {
                const text = String(value || '').trim();
                if (text.length <= limit) return text;
                return text.slice(0, limit - 1) + '...';
            },
            fallbackImage(section, id = null) {
                const code = this.normalize(section);
                const index = Math.abs(parseInt(id || 0, 10)) || 0;
                if (code === 'food' || code === 'restaurant') {
                    const list = [
                        'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80',
                        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
                        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=900&q=80',
                    ];
                    return list[index % list.length];
                }
                if (code === 'freight' || code === 'moving' || code === 'tow' || code === 'roadside') {
                    const list = [
                        'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=900&q=80',
                        'https://images.unsplash.com/photo-1616401784845-180882ba9ba8?auto=format&fit=crop&w=900&q=80',
                        'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=900&q=80',
                    ];
                    return list[index % list.length];
                }
                const list = [
                    'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1573246123716-6b1782bfc499?auto=format&fit=crop&w=900&q=80',
                ];
                return list[index % list.length];
            },
            extractEta(card) {
                const min = Number(card.eta_min || 0);
                const max = Number(card.eta_max || 0);
                if (min > 0 && max > 0) return `${min}-${max} min ETA`;
                const lines = Array.isArray(card.items) ? card.items : [];
                const etaLine = lines.find((line) => this.normalize(line.name || '').includes('eta'));
                return etaLine ? String(etaLine.name) : 'Live ETA';
            },
            formatMoney(value) {
                return this.currencyFormatter.format(Number(value) || 0);
            },
            getItemQty(id) {
                const row = this.cart.find((item) => String(item.id) === String(id));
                return row ? row.qty : 0;
            },
            addToCart(card) {
                const existing = this.cart.find((item) => String(item.id) === String(card.id));
                if (existing) {
                    existing.qty += 1;
                    return;
                }
                this.cart.push({ ...card, qty: 1 });
            },
            increaseQty(id) {
                const row = this.cart.find((item) => String(item.id) === String(id));
                if (row) row.qty += 1;
            },
            decreaseQty(id) {
                const row = this.cart.find((item) => String(item.id) === String(id));
                if (!row) return;
                row.qty -= 1;
                if (row.qty <= 0) this.removeItem(id);
            },
            removeItem(id) {
                this.cart = this.cart.filter((item) => String(item.id) !== String(id));
            },
            buyNow(card) {
                this.addToCart(card);
                this.openCart();
            },
            saveDraft() {
                try {
                    const payload = {
                        customer: this.form.customer,
                        address: this.form.address,
                        notes: this.form.notes,
                    };
                    localStorage.setItem(this.checkoutDraftKey, JSON.stringify(payload));
                } catch (error) {
                    // ignore storage errors
                }
            },
            hydrateDraft() {
                try {
                    const raw = localStorage.getItem(this.checkoutDraftKey);
                    if (!raw) return;
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        this.form.customer = { ...this.form.customer, ...(parsed.customer || {}) };
                        this.form.address = { ...this.form.address, ...(parsed.address || {}) };
                        this.form.notes = typeof parsed.notes === 'string' ? parsed.notes : this.form.notes;
                    }
                } catch (error) {
                    // ignore invalid local draft
                }
            },
            normalizeSlot(rawSlot) {
                const from = rawSlot.from || rawSlot.start || this.readTime(rawSlot.start_at);
                const to = rawSlot.to || rawSlot.end || this.readTime(rawSlot.end_at);
                return {
                    id: rawSlot.id,
                    name: rawSlot.name || rawSlot.label || (`Slot #${rawSlot.id}`),
                    code: rawSlot.code || null,
                    from: from || '--:--',
                    to: to || '--:--',
                };
            },
            readTime(input) {
                if (!input) return null;
                if (typeof input === 'string' && input.length >= 5 && input.includes(':') && !input.includes('T')) {
                    return input.slice(0, 5);
                }
                try {
                    const date = new Date(input);
                    if (Number.isNaN(date.getTime())) return null;
                    return date.toLocaleTimeString('en-NO', { hour: '2-digit', minute: '2-digit' });
                } catch (error) {
                    return null;
                }
            },
            async loadSlots() {
                if (!this.deliveryDate) return;
                if (this.slotAbortController) this.slotAbortController.abort();
                this.slotAbortController = new AbortController();
                this.slotLoading = true;
                this.slotError = '';

                const zone = this.selectedZoneId ? '&zone_id=' + encodeURIComponent(this.selectedZoneId) : '';
                const url = '/api/v1/public/slots?date=' + encodeURIComponent(this.deliveryDate) + zone;

                try {
                    const response = await fetch(url, { signal: this.slotAbortController.signal });
                    if (!response.ok) {
                        throw new Error('Unable to load slots');
                    }
                    const payload = await response.json();
                    const rows = Array.isArray(payload && payload.data) ? payload.data : [];
                    this.slots = rows.map((row) => this.normalizeSlot(row));
                    if (!this.slots.find((slot) => String(slot.id) === String(this.selectedSlotId))) {
                        this.selectedSlotId = this.slots.length ? String(this.slots[0].id) : '';
                    }
                } catch (error) {
                    if (error && error.name === 'AbortError') return;
                    this.slots = [];
                    this.selectedSlotId = '';
                    this.slotError = 'Failed to load slots. Retry by changing date or zone.';
                } finally {
                    this.slotLoading = false;
                }
            },
            async submitOrder() {
                if (this.cart.length === 0 || !this.selectedSlotId) {
                    this.resultOk = false;
                    this.resultMessage = 'Add at least one item and select a slot.';
                    return;
                }

                const hasService = this.cart.some((item) => Number(item.service_id || this.deliveryServiceId) > 0);
                if (!hasService) {
                    this.resultOk = false;
                    this.resultMessage = 'Delivery service is not configured for current items.';
                    return;
                }

                this.submitting = true;
                this.resultMessage = '';
                this.saveDraft();

                const itemsPayload = this.cart.map((item) => ({
                    service_id: Number(item.service_id || this.deliveryServiceId),
                    quantity: item.qty,
                    notes: [item.title, item.store, item.section].filter(Boolean).join(' | '),
                    title: item.title,
                    description: item.description,
                    unit_price: Number(item.price) || 0,
                    image_url: item.image_url || null,
                    product_id: String(item.id),
                    store_id: item.source_id ? String(item.source_id) : (item.store ? String(item.store) : null),
                    metadata: {
                        section: item.section || null,
                        source_type: item.source_type || null,
                        source_id: item.source_id || null,
                        source_url: item.source_url || null,
                        lines: Array.isArray(item.items) ? item.items : [],
                    }
                }));

                const payload = {
                    customer: this.form.customer,
                    address: this.form.address,
                    slot_id: this.selectedSlotId,
                    payment_provider: 'stripe',
                    notes: this.form.notes,
                    items: itemsPayload
                };

                try {
                    const response = await fetch('/api/v1/public/orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error((data && data.message) || 'Order failed.');
                    }

                    this.resultOk = true;
                    this.resultMessage = 'Order ' + data.data.order_number + ' created successfully.';
                    this.cart = [];
                    setTimeout(() => {
                        if (this.resultOk) this.closeCart();
                    }, 2200);
                } catch (error) {
                    this.resultOk = false;
                    this.resultMessage = error.message || 'Unable to submit order.';
                } finally {
                    this.submitting = false;
                }
            }
        };
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/bikube/resources/views/public/delivery-landing.blade.php ENDPATH**/ ?>