@extends('layouts.app')

@section('title', $ad->title . ' — Объявление')
@section('meta_description', \Illuminate\Support\Str::limit($ad->description, 160))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        {{-- Breadcrumbs --}}
        <nav class="text-sm mb-6 text-gray-500">
            <a href="/" class="hover:text-primary-600">Главная</a> <span class="mx-2">/</span>
            <a href="{{ route('classifieds.index') }}" class="hover:text-primary-600">Объявления</a> <span class="mx-2">/</span>
            <span class="text-gray-800">{{ $ad->category->name ?? 'Без категории' }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Image Gallery --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" 
                     x-data="{ 
                         activeImage: '{{ $ad->main_image_url }}',
                         images: {{ json_encode($ad->images->map(fn($i) => $i->url)->toArray()) }}
                     }">
                    <div class="relative h-[400px] md:h-[500px] bg-gray-100 flex items-center justify-center bg-gray-900">
                        <img :src="activeImage" class="w-full h-full object-contain">
                        
                        @if($ad->vip_expires_at && $ad->vip_expires_at->isFuture())
                            <div class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 font-bold px-3 py-1 rounded shadow-lg flex items-center gap-1 z-10">
                                👑 VIP Listing
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2 p-4 overflow-x-auto bg-white">
                        @if($ad->images->count() > 0)
                            @foreach($ad->images as $img)
                                <div @click="activeImage = '{{ $img->url }}'" 
                                     class="w-20 h-20 bg-gray-100 rounded-lg cursor-pointer hover:opacity-100 transition border-2"
                                     :class="activeImage === '{{ $img->url }}' ? 'border-blue-600 opacity-100' : 'border-transparent opacity-60'">
                                    <img src="{{ $img->url }}" class="w-full h-full object-cover rounded-md">
                                </div>
                            @endforeach
                        @else
                            <div class="text-sm text-gray-400 p-2">No additional images</div>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-4">{{ $ad->title }}</h1>
                    <div class="flex items-center gap-6 text-sm text-gray-500 mb-8 pb-8 border-b border-gray-100 flex-wrap">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $ad->address ?? 'Адрес не указан' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Опубликовано {{ optional($ad->published_at ?? $ad->created_at)->format('d M Y') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            {{ number_format($ad->views_count ?? 0, 0, ',', ' ') }} просмотров
                        </span>
                    </div>
                    <div class="prose max-w-none text-gray-700">
                        <h3 class="font-bold text-lg text-gray-900 mb-2">Описание</h3>
                        {!! nl2br(e($ad->description)) !!}
                    </div>

                    {{-- Map --}}
                    @if($ad->lat && $ad->lng)
                        <div class="mt-8">
                            <h3 class="font-bold text-lg text-gray-900 mb-4">Местоположение</h3>
                            <div class="rounded-xl overflow-hidden h-64 relative bg-gray-100 border">
                                <div id="map" class="w-full h-full"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Safety Tips --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 flex gap-4">
                    <div class="bg-blue-100 p-2 rounded-full h-fit text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-blue-900">Советы по безопасности</h4>
                        <p class="text-sm text-blue-800 mt-1">Никогда не переводите деньги заранее. Встречайтесь в общественных местах. Используйте нашу безопасную систему оплаты, если она доступна.</p>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Price & Actions --}}
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sticky top-6">
                    <div class="text-3xl font-black text-gray-900 mb-1">
                        @if($ad->price_value)
                            {{ number_format($ad->price_value / 100, 0, ',', ' ') }} NOK
                        @else
                            По договорённости
                        @endif
                    </div>
                    <p class="text-gray-500 text-sm mb-6">Цена договорная</p>
                    <div class="space-y-3">
                        @auth
                            @if(auth()->id() !== $ad->user_id)
                                <button onclick="document.getElementById('chat-section')?.scrollIntoView({behavior: 'smooth'}); document.querySelector('[data-chat-input]')?.focus()" 
                                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 flex justify-center items-center gap-2 mb-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    Начать чат
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}" 
                               class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 flex justify-center items-center gap-2 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                Войти для чата
                            </a>
                        @endauth

                        @auth
                            @if(auth()->id() !== $ad->user_id)
                                <a href="{{ url('/checkout/classifieds.delivery?ad_id='.$ad->id) }}" class="w-full bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-700 font-bold py-3.5 px-4 rounded-xl transition flex justify-center items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                        </svg>
                                        Заказать доставку этого товара
                                </a>
                                
                                <livewire:ad-favorite-button :ad="$ad" />
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="w-full bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-700 font-bold py-3.5 px-4 rounded-xl transition flex justify-center items-center gap-2 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                Войти для избранного
                            </a>
                        @endauth
                    </div>

                    {{-- Seller Info --}}
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <div class="flex items-center gap-4">
                            @if($ad->shop && $ad->shop->logo_path)
                                <img src="{{ asset('storage/'.$ad->shop->logo_path) }}" 
                                     alt="{{ $ad->shop->name }}"
                                     class="w-14 h-14 rounded-full object-cover border-2 border-gray-200">
                            @else
                                <div class="w-14 h-14 bg-gray-200 rounded-full flex items-center justify-center text-xl font-bold text-gray-500">
                                    {{ substr($ad->user->name ?? 'U', 0, 1) }}
                                </div>
                            @endif
                            <div>
                                @if($ad->shop)
                                    <a href="{{ route('shops.show', $ad->shop->slug) }}" class="font-bold text-gray-900 hover:text-primary-600">
                                        {{ $ad->shop->name }}
                                    </a>
                                    <div class="text-sm text-green-600 flex items-center gap-1">
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span> Магазин
                                    </div>
                                @else
                                    <div class="font-bold text-gray-900">{{ $ad->user->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-green-600 flex items-center gap-1">
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span> Онлайн
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            @if($ad->shop)
                                <a href="{{ route('shops.show', $ad->shop->slug) }}" class="flex-1 text-sm font-semibold text-gray-600 border rounded-lg py-2 text-center hover:bg-gray-50">
                                    Профиль магазина
                                </a>
                            @endif
                            <a href="{{ route('classifieds.index', ['seller_type' => $ad->shop ? 'shop' : 'private']) }}" class="flex-1 text-sm font-semibold text-gray-600 border rounded-lg py-2 text-center hover:bg-gray-50">
                                Все объявления
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat --}}
        @auth
            @if(auth()->id() !== $ad->user_id)
                <div id="chat-section" class="mt-12 bg-white rounded-2xl shadow-sm border p-6">
                    <livewire:ad-chat :ad="$ad" />
                </div>
            @endif
        @endauth
    </div>
</div>

@if($ad->lat && $ad->lng)
@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script>
    mapboxgl.accessToken = '{{ config("services.mapbox.token", "") }}';
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [{{ $ad->lng }}, {{ $ad->lat }}],
        zoom: 14
    });
    
    new mapboxgl.Marker({ color: '#2563eb' })
        .setLngLat([{{ $ad->lng }}, {{ $ad->lat }}])
        .setPopup(new mapboxgl.Popup().setHTML('<strong>{{ $ad->title }}</strong><br>{{ $ad->address }}'))
        .addTo(map);
</script>
@endpush
@endif
@endsection
