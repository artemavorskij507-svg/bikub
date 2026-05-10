@extends('layouts.app')

@section('title', 'Заявка принята — GLF Bikube')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Success Banner --}}
        <div class="bg-green-600 text-white rounded-xl p-8 text-center mb-8">
            <div class="text-6xl mb-4">✅</div>
            <h1 class="text-3xl font-bold mb-2">Заявка принята!</h1>
            <p class="text-lg opacity-90">Мы уже работаем над вашей проблемой</p>
        </div>

        {{-- Order Info --}}
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-8 mb-6">
            <h2 class="text-2xl font-semibold text-slate-900 mb-6">Информация о заявке</h2>
            
            <div class="space-y-4">
                <div>
                    <div class="text-sm text-slate-600 mb-1">Номер заявки</div>
                    <div class="text-xl font-semibold text-slate-900">#{{ $emergency->id }}</div>
                </div>

                @if($order)
                    <div>
                        <div class="text-sm text-slate-600 mb-1">Номер заказа</div>
                        <div class="text-xl font-semibold text-slate-900">#{{ $order->order_number ?? $order->id }}</div>
                    </div>
                @endif

                <div>
                    <div class="text-sm text-slate-600 mb-1">Статус</div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        @if($is_inspection ?? false)
                            Заявка на осмотр
                        @else
                            Новая заявка
                        @endif
                    </span>
                </div>

                @php
                    $trackingUrl = null;
                    if (isset($is_inspection) && $is_inspection) {
                        // Для VehicleInspectionRequest поки немає tracking_url
                        $trackingUrl = null;
                    } else {
                        $trackingUrl = $emergency->tracking_url ?? null;
                    }
                @endphp
                
                @if($trackingUrl)
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 mb-3">Отслеживание заявки</h3>
                        <p class="text-sm text-slate-600 mb-4">
                            Вы можете отслеживать статус вашей заявки по ссылке ниже. Мы также отправим вам эту ссылку в SMS/мессенджер.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ $trackingUrl }}" 
                               target="_blank"
                               class="bg-sky-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-sky-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Открыть страницу отслеживания
                            </a>
                            <button onclick="navigator.clipboard.writeText('{{ $trackingUrl }}').then(() => alert('Ссылка скопирована!'))" 
                                    class="bg-slate-200 text-slate-700 px-6 py-3 rounded-lg text-center font-semibold hover:bg-slate-300 transition-colors">
                                📋 Копировать ссылку
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-3 break-all">{{ $trackingUrl }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Next Steps --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-3">Что дальше?</h3>
            <ul class="space-y-2 text-slate-700">
                <li class="flex items-start">
                    <span class="mr-2">1.</span>
                    <span>Диспетчер обработает вашу заявку в ближайшее время</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">2.</span>
                    <span>Мы свяжемся с вами по указанному телефону</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">3.</span>
                    <span>Вы можете отслеживать статус заявки по ссылке выше</span>
                </li>
            </ul>
        </div>

        {{-- Contact Info --}}
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-6 text-center">
            <p class="text-slate-600 mb-2">Нужна срочная помощь?</p>
            <p class="text-slate-900 font-semibold text-lg">
                Звоните: <a href="tel:+4712345678" class="text-sky-600 hover:underline">+47 12 34 56 78</a>
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex justify-center mt-6">
            <a href="{{ route('home') }}" 
               class="px-6 py-3 bg-slate-600 text-white rounded-lg font-semibold hover:bg-slate-700 transition-colors">
                Вернуться на главную
            </a>
        </div>
    </div>
</div>
@endsection

