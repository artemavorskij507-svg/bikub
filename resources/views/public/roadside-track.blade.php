@extends('layouts.app')

@section('title', 'Статус заявки на помощь на дороге')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Статус вашей заявки</h1>
            <p class="text-slate-600">Помощь на дороге / Эвакуатор</p>
        </div>

        {{-- Main Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            {{-- Service Type & Status --}}
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-200">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $serviceTypeLabel }}</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Заявка #{{ $emergency->id }}
                        @if($emergency->order)
                            • Заказ {{ $emergency->order->order_number }}
                        @endif
                    </p>
                </div>
                <div>
                    @php
                        $statusColors = [
                            'new' => 'bg-yellow-100 text-yellow-800',
                            'assigned' => 'bg-blue-100 text-blue-800',
                            'on_route' => 'bg-indigo-100 text-indigo-800',
                            'in_progress' => 'bg-purple-100 text-purple-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-gray-100 text-gray-800',
                        ];
                        $statusLabels = [
                            'new' => 'Новый',
                            'assigned' => 'Назначен',
                            'on_route' => 'В пути',
                            'in_progress' => 'В работе',
                            'completed' => 'Завершен',
                            'failed' => 'Не удалось',
                            'cancelled' => 'Отменен',
                        ];
                    @endphp
                    <span class="px-4 py-2 rounded-full text-sm font-medium {{ $statusColors[$emergency->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $statusLabels[$emergency->status] ?? $emergency->status }}
                    </span>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm font-medium text-slate-500">Клиент</label>
                    <p class="text-slate-900 font-medium">
                        {{ $emergency->customer->name ?? ($emergency->metadata['full_name'] ?? 'N/A') }}
                    </p>
                </div>
                @if($emergency->metadata['vehicle_plate'] ?? null)
                    <div>
                        <label class="text-sm font-medium text-slate-500">Госномер</label>
                        <p class="text-slate-900 font-medium">{{ $emergency->metadata['vehicle_plate'] }}</p>
                    </div>
                @endif
            </div>

            {{-- Problem Description --}}
            @if($emergency->incident_description)
                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-500">Описание проблемы</label>
                    <p class="text-slate-900 mt-1">{{ $emergency->incident_description }}</p>
                </div>
            @endif

            {{-- Location --}}
            @if($emergency->metadata['location_text'] ?? null)
                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-500">Местонахождение</label>
                    <p class="text-slate-900 mt-1">{{ $emergency->metadata['location_text'] }}</p>
                    @if($emergency->lat && $emergency->lng)
                        <a href="https://www.google.com/maps?q={{ $emergency->lat }},{{ $emergency->lng }}" 
                           target="_blank"
                           class="text-sm text-sky-600 hover:underline mt-1 inline-block">
                            Открыть на карте
                        </a>
                    @endif
                </div>
            @endif

            {{-- Geo Zone --}}
            @if($emergency->order?->geoZone)
                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-500">Зона обслуживания</label>
                    <p class="text-slate-900 mt-1">{{ $emergency->order->geoZone->name }}</p>
                </div>
            @endif
        </div>

        {{-- Current Status Description --}}
        <div class="bg-sky-50 border border-sky-200 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Что происходит сейчас</h3>
            <p class="text-slate-700">{{ $emergency->getCurrentStatusDescription() }}</p>
            
            @if($emergency->order?->assignedUser)
                <div class="mt-4 pt-4 border-t border-sky-200">
                    <p class="text-sm text-slate-600">
                        Исполнитель: <span class="font-medium text-slate-900">{{ $emergency->order->assignedUser->name }}</span>
                    </p>
                </div>
            @endif
            
            @if($emergency->partner)
                <div class="mt-2">
                    <p class="text-sm text-slate-600">
                        Партнёр: <span class="font-medium text-slate-900">{{ $emergency->partner->name }}</span>
                        @if($emergency->partner->phone)
                            <a href="tel:{{ $emergency->partner->phone }}" class="text-sky-600 hover:underline ml-2">
                                {{ $emergency->partner->phone }}
                            </a>
                        @endif
                    </p>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Ход выполнения</h3>
            <div class="space-y-4">
                @foreach($timeline as $index => $step)
                    <div class="flex items-start">
                        {{-- Timeline dot --}}
                        <div class="flex-shrink-0 mr-4">
                            @if($step['completed'])
                                <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full border-2 border-slate-300 bg-white"></div>
                            @endif
                        </div>
                        
                        {{-- Timeline content --}}
                        <div class="flex-1 {{ $step['completed'] ? '' : 'opacity-60' }}">
                            <p class="font-medium text-slate-900">{{ $step['label'] }}</p>
                            @if($step['at'])
                                <p class="text-sm text-slate-500 mt-1">
                                    {{ \Carbon\Carbon::parse($step['at'])->format('d.m.Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Connector line (except last) --}}
                    @if($index < count($timeline) - 1)
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-0.5 h-6 {{ $step['completed'] ? 'bg-green-500' : 'bg-slate-300' }} ml-4"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Map Placeholder --}}
        @if($emergency->lat && $emergency->lng)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Местоположение</h3>
                <div class="bg-slate-100 rounded-lg h-64 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-slate-500 text-sm">Здесь позже будет карта с прямым трекингом эвакуатора</p>
                        <a href="https://www.google.com/maps?q={{ $emergency->lat }},{{ $emergency->lng }}" 
                           target="_blank"
                           class="text-sky-600 hover:underline text-sm mt-2 inline-block">
                            Открыть в Google Maps
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Contact Info --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 text-center">
            <p class="text-slate-600 mb-2">Нужна помощь?</p>
            <p class="text-slate-900 font-semibold">
                Звоните: <a href="tel:+4712345678" class="text-sky-600 hover:underline">+47 12 34 56 78</a>
            </p>
        </div>
    </div>
</div>
@endsection

