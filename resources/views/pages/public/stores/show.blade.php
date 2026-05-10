@extends('layouts.app')

@section('title', $store->name . ' — GLF Bikube')

@section('content')
<div class="bg-slate-50 py-12" x-data="shoppingList" x-init="init()">
    <div class="max-w-6xl mx-auto px-4 lg:px-6 space-y-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                @if($store->logo_url)
                    @php($logoUrl = \Illuminate\Support\Str::startsWith($store->logo_url, ['http://', 'https://'])
                        ? $store->logo_url
                        : \Illuminate\Support\Facades\Storage::url($store->logo_url))
                    <img src="{{ $logoUrl }}" alt="{{ $store->name }}" class="h-12 w-auto object-contain rounded-xl bg-white p-2 shadow-sm">
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $store->name }}</h1>
                    @if($store->zone)
                        <p class="text-sm text-slate-500">{{ $store->zone->name }}</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('public.catalog.index', ['category' => 'delivery']) }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                <span>Назад к доставке</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </a>
        </div>

        @if($store->products->isEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
                Для этого магазина пока нет доступных товаров.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($store->products as $product)
                    @php
                        $imageUrl = $product->image_url
                            ? (\Illuminate\Support\Str::startsWith($product->image_url, ['http://', 'https://'])
                                ? $product->image_url
                                : \Illuminate\Support\Facades\Storage::url($product->image_url))
                            : asset('images/placeholder.png');
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col h-full">
                        <a href="{{ route('public.product.show', $product) }}" class="block">
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-40 object-contain mb-4">
                        </a>
                        <h3 class="text-lg font-semibold text-slate-900 mb-1 leading-tight">
                            <a href="{{ route('public.product.show', $product) }}" class="hover:underline">{{ $product->name }}</a>
                        </h3>
                        <p class="text-sm text-slate-500 mb-3 line-clamp-2">{{ strip_tags($product->description ?? '') }}</p>
                        <div class="text-xl font-bold text-slate-900 mb-4">
                            ~ {{ number_format($product->pivot->price / 100, 2, ',', ' ') }} kr
                        </div>
                        <button
                            type="button"
                            class="mt-auto w-full inline-flex items-center justify-center gap-2 bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition"
                            @click="add({
                                product_id: {{ $product->id }},
                                name: {{ Js::from($product->name) }},
                                image_url: {{ Js::from($imageUrl) }}
                            }, {{ $store->id }}, {{ Js::from($store->name) }})"
                        >
                            В корзину
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
