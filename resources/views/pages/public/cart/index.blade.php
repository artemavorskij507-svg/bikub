@extends('layouts.app')

@section('title', 'Ваш список покупок — GLF Bikube')

@section('content')
<div x-data="shoppingList" x-init="init()" class="bg-slate-50 py-12 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 lg:px-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Ваш список покупок</h1>
                <p class="text-slate-500 mt-1">
                    Добавляйте товары на страницах каталога и сравнивайте цены перед заказом.
                </p>
                <template x-if="storeName">
                    <p class="mt-2 text-sm text-slate-500">
                        Магазин: <span class="font-medium text-slate-700" x-text="storeName"></span>
                    </p>
                </template>
            </div>
            <a href="{{ route('public.catalog.index') }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                <span>Вернуться к каталогу</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6 16.5 12 10.5 18M7.5 12h9" />
                </svg>
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('cart'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('cart') }}
            </div>
        @endif

        <template x-if="items.length > 0">
            <div>
                <div class="grid gap-4">
                    <template x-for="item in items" :key="item.product_id">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <img
                                    :src="item._display?.image_url || {{ Js::from(asset('images/placeholder.png')) }}"
                                    :alt="item._display?.name || 'Product image'"
                                    class="h-16 w-16 md:h-20 md:w-20 object-cover rounded-xl bg-slate-100"
                                >
                                <div>
                                    <p class="text-lg font-semibold text-slate-900" x-text="item._display?.name || 'Товар'"></p>
                                    <div class="flex items-center gap-3 text-sm text-slate-500 mt-2">
                                        <span>Количество:</span>
                                        <div class="inline-flex items-center gap-2 bg-slate-100 rounded-full px-3 py-1">
                                            <button type="button" class="text-slate-600 hover:text-slate-800" @click="decrement(item.product_id)">−</button>
                                            <span class="font-medium text-slate-800" x-text="item.quantity"></span>
                                            <button type="button" class="text-slate-600 hover:text-slate-800" @click="increment(item.product_id)">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button
                                    type="button"
                                    class="text-red-500 hover:text-red-600"
                                    @click="remove(item.product_id)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="text-sm text-slate-500">
                        Всего позиций:
                        <span class="font-semibold text-slate-800" x-text="items.length"></span>,
                        всего товаров:
                        <span class="font-semibold text-slate-800" x-text="totalItems"></span>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-center gap-3">
                        <button
                            type="button"
                            class="text-sm text-slate-500 hover:text-red-600"
                            @click="clear()"
                        >
                            Очистить список
                        </button>
                        <form action="{{ route('public.cart.optimize') }}" method="POST" class="flex items-center gap-3">
                            @csrf
                            <input type="hidden" name="store_id" :value="storeId ?? ''">
                            <template x-for="(item, index) in items" :key="`hidden-${item.product_id}`">
                                <div class="hidden">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                </div>
                            </template>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-5 rounded-xl transition"
                            >
                                Найти лучшие цены
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 0 1 .894.553l6 12A1 1 0 0 1 16 17H4a1 1 0 0 1-.894-1.447l6-12A1 1 0 0 1 10 3Zm0 3.618L5.618 15h8.764L10 6.618Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="items.length === 0">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm py-16 px-6 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400 text-2xl mb-4">
                    🛒
                </div>
                <h2 class="text-xl font-semibold text-slate-800">Список пока пуст</h2>
                <p class="text-slate-500 mt-2">
                    Добавляйте товары со страниц каталога или воспользуйтесь поиском.
                </p>
                <a
                    href="{{ route('public.catalog.index') }}"
                    class="mt-6 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition"
                >
                    Перейти в каталог
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 5.25 21 12m0 0-7.5 6.75M21 12H3" />
                    </svg>
                </a>
            </div>
        </template>
    </div>
</div>
@endsection

