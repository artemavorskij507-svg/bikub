@extends('layouts.app')

@section('title', $product->name . ' — GLF Bikube')

@section('content')
<div x-data="shoppingList" x-init="init()" class="bg-slate-50 py-12">
    <div class="max-w-6xl mx-auto px-4 lg:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div>
                <div class="rounded-3xl overflow-hidden bg-white shadow">
                    <img
                        src="{{ $product->image_url ?? asset('images/placeholder.png') }}"
                        alt="{{ $product->name }}"
                        class="w-full h-80 object-cover"
                    >
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3 text-sm text-slate-500 mb-4">
                    <a href="{{ route('public.catalog.index') }}" class="hover:text-slate-700">Каталог</a>
                    <span>→</span>
                    <span class="text-slate-700 font-medium">{{ $product->name }}</span>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">{{ $product->name }}</h1>

                @if($product->description)
                    <div class="prose prose-slate max-w-none mb-8">
                        {!! $product->description !!}
                    </div>
                @else
                    <p class="text-slate-600 mb-8">
                        Мы собираем актуальные данные о ценах на этот товар в магазинах Нарвика. Товар может быть доступен также по спецзаказу.
                    </p>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-800">Где купить</h2>
                        <span class="text-sm text-slate-500">{{ $product->stores->count() }} магазин(ов)</span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($product->stores as $store)
                            <div class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    @if($store->logo_url)
                                        @php($logo = \Illuminate\Support\Str::startsWith($store->logo_url, ['http://', 'https://']) ? $store->logo_url : \Illuminate\Support\Facades\Storage::url($store->logo_url))
                                        <img src="{{ $logo }}" alt="{{ $store->name }}" class="h-12 w-12 object-contain rounded-lg bg-slate-100">
                                    @else
                                        <div class="h-12 w-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 font-semibold">
                                            {{ mb_substr($store->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-base font-medium text-slate-900">
                                            <a href="{{ route('public.store.show', $store) }}" class="hover:underline">{{ $store->name }}</a>
                                        </div>
                                        @if($store->zone)
                                            <div class="text-sm text-slate-500">{{ $store->zone->name }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right md:text-left">
                                    <div class="text-xl font-semibold text-slate-900">
                                        {{ number_format($store->pivot->price / 100, 2, ',', ' ') }} kr
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">Цена обновлена {{ optional($store->pivot->updated_at)->diffForHumans() ?? 'недавно' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-sm text-slate-500">
                                На данный момент цены по магазинам не найдены. Мы обновляем данные ежедневно — загляните позже или напишите нам, если хотите получить оповещение.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-8">
                    <button
                        type="button"
                        @click="add({{ Js::from($product->only(['id', 'name', 'image_url'])) }})"
                        class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition"
                    >
                        Добавить в список покупок
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

