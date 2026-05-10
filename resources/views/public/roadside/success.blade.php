@extends('layouts.app')

@section('title', 'Запрос принят - Помощь на дороге')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <!-- Success Banner -->
        <div class="bg-green-600 text-white rounded-xl p-8 text-center mb-8">
            <div class="text-6xl mb-4">✅</div>
            <h1 class="text-3xl font-bold mb-2">Запрос принят!</h1>
            <p class="text-lg opacity-90">Мы уже работаем над вашей проблемой</p>
        </div>

        <!-- Emergency Info Card -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
            <h2 class="text-2xl font-semibold text-slate-900 mb-6">Информация о запросе</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <div class="text-sm text-slate-600 mb-1">Номер запроса</div>
                    <div class="text-xl font-semibold text-slate-900">#{{ $emergency->id }}</div>
                </div>
                <div>
                    <div class="text-sm text-slate-600 mb-1">Тип проблемы</div>
                    <div class="text-xl font-semibold text-slate-900">
                        @php
                            $types = [
                                'jump_start' => 'Прикуривание',
                                'fuel' => 'Закончилось топливо',
                                'flat_tire' => 'Прокол колеса',
                                'locked_keys' => 'Закрыты ключи',
                                'engine_no_start' => 'Не заводится',
                                'tow_needed' => 'Нужен эвакуатор',
                                'accident' => 'ДТП',
                            ];
                        @endphp
                        {{ $types[$emergency->incident_type] ?? $emergency->incident_type }}
                    </div>
                </div>
                <div>
                    <div class="text-sm text-slate-600 mb-1">Статус</div>
                    <div class="text-xl font-semibold">
                        @php
                            $statuses = [
                                'new' => 'Новый',
                                'assigned' => 'Назначен',
                                'on_route' => 'В пути',
                                'in_progress' => 'В работе',
                                'completed' => 'Завершен',
                            ];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-sm
                            @if($emergency->status === 'new') bg-yellow-100 text-yellow-800
                            @elseif($emergency->status === 'assigned') bg-blue-100 text-blue-800
                            @elseif($emergency->status === 'in_progress') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $statuses[$emergency->status] ?? $emergency->status }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="text-sm text-slate-600 mb-1">Время создания</div>
                    <div class="text-xl font-semibold text-slate-900">
                        {{ $emergency->created_at->format('d.m.Y H:i') }}
                    </div>
                </div>
            </div>

            <!-- Назначенный исполнитель -->
            @if($emergency->helper && $emergency->helper->user)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl">👷</div>
                        <div>
                            <div class="font-semibold text-slate-900">Дорожный помощник назначен</div>
                            <div class="text-sm text-slate-600">
                                {{ $emergency->helper->user->name ?? 'Помощник #' . $emergency->helper->id }}
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($emergency->partner)
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl">🚛</div>
                        <div>
                            <div class="font-semibold text-slate-900">Партнёр-эвакуатор назначен</div>
                            <div class="text-sm text-slate-600">{{ $emergency->partner->name }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl">⏳</div>
                        <div>
                            <div class="font-semibold text-slate-900">Ожидание назначения</div>
                            <div class="text-sm text-slate-600">Мы ищем ближайшего помощника</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ETA (mock) -->
            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="text-3xl">⏱️</div>
                    <div>
                        <div class="font-semibold text-slate-900">Примерное время прибытия</div>
                        <div class="text-lg text-slate-700">15-30 минут</div>
                    </div>
                </div>
            </div>

            <!-- Карта -->
            @if($emergency->lat && $emergency->lng)
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-700 mb-2">Местоположение</div>
                    <div id="map" class="w-full h-64 rounded-lg border border-slate-300"></div>
                    <div class="mt-2 text-sm text-slate-600">
                        Координаты: {{ number_format($emergency->lat, 6) }}, {{ number_format($emergency->lng, 6) }}
                    </div>
                </div>
            @endif

            <!-- Фото -->
            @if($emergency->photos && count($emergency->photos) > 0)
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-700 mb-2">Загруженные фото</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($emergency->photos as $photo)
                            <img src="{{ asset('storage/' . $photo) }}" alt="Photo" class="w-full h-24 object-cover rounded-lg">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Tracking Link -->
        @if($emergency->tracking_url)
            <div class="bg-sky-50 border border-sky-200 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Отслеживание заявки</h3>
                <p class="text-slate-600 mb-4">Вы можете отслеживать статус вашей заявки по ссылке ниже. Мы также отправим вам эту ссылку в SMS/мессенджер.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ $emergency->tracking_url }}" 
                       target="_blank"
                       class="bg-sky-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-sky-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Открыть страницу отслеживания
                    </a>
                    <button onclick="navigator.clipboard.writeText('{{ $emergency->tracking_url }}').then(() => alert('Ссылка скопирована!'))" 
                            class="bg-slate-200 text-slate-700 px-6 py-3 rounded-lg text-center font-semibold hover:bg-slate-300 transition-colors">
                        📋 Копировать ссылку
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-3 break-all">{{ $emergency->tracking_url }}</p>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @if($emergency->helper && $emergency->helper->user && $emergency->helper->user->phone)
                <a href="tel:{{ $emergency->helper->user->phone }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-blue-700 transition-colors">
                    📞 Связаться с помощником
                </a>
            @elseif($emergency->partner && $emergency->partner->contacts->first())
                <a href="tel:{{ $emergency->partner->contacts->first()->phone }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-blue-700 transition-colors">
                    📞 Связаться с партнёром
                </a>
            @endif
            <a href="{{ route('home') }}" class="bg-slate-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-slate-700 transition-colors">
                🏠 Вернуться на главную
            </a>
        </div>
    </div>
</div>

@push('scripts')
@if($emergency->lat && $emergency->lng)
<script>
    // Простая карта (можно заменить на Leaflet/Google Maps)
    function initMap() {
        const mapDiv = document.getElementById('map');
        if (!mapDiv) return;
        
        const lat = {{ $emergency->lat }};
        const lng = {{ $emergency->lng }};
        
        // Используем статическую карту (можно заменить на интерактивную)
        const mapUrl = `https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ef4444(${lng},${lat})/${lng},${lat},14,0/600x300@2x?access_token=`;
        
        mapDiv.innerHTML = `<img src="${mapUrl}" alt="Map" class="w-full h-full object-cover rounded-lg">`;
    }
    
    initMap();
</script>
@endif
@endpush
@endsection

