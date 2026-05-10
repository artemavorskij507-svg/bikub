@extends('layouts.app')

@section('title', 'Заявка отправлена - Помощь на дороге')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="text-6xl mb-6">✅</div>
        <h1 class="text-3xl font-bold text-slate-900 mb-4">Заявка отправлена!</h1>
        <p class="text-lg text-slate-600 mb-8">
            Мы получили ваш запрос. Диспетчер скоро свяжется с вами.
        </p>

        @if($order)
            <div class="bg-slate-50 rounded-lg p-6 mb-6 text-left">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Информация о заявке</h2>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="font-medium text-slate-700">Номер заказа:</span>
                        <span class="ml-2 font-mono text-slate-900">{{ $order->order_number ?? '#' . $order->id }}</span>
                    </div>
                    @if($order->roadsideDetails)
                        <div>
                            <span class="font-medium text-slate-700">Адрес инцидента:</span>
                            <span class="ml-2 text-slate-900">{{ $order->roadsideDetails->incident_address }}</span>
                        </div>
                        @if($order->roadsideDetails->vehicle_make || $order->roadsideDetails->vehicle_model)
                            <div>
                                <span class="font-medium text-slate-700">Автомобиль:</span>
                                <span class="ml-2 text-slate-900">
                                    {{ $order->roadsideDetails->vehicle_make }}
                                    {{ $order->roadsideDetails->vehicle_model }}
                                    @if($order->roadsideDetails->vehicle_plate)
                                        ({{ $order->roadsideDetails->vehicle_plate }})
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('public.roadside.index') }}" class="inline-block px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                Вернуться к услугам
            </a>
            <a href="{{ route('home') }}" class="inline-block px-6 py-3 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                На главную
            </a>
        </div>
    </div>
</div>
@endsection

