@extends('layouts.app')

@section('title', 'Выберите лучший вариант — GLF Bikube')

@section('content')
<div class="bg-slate-50 py-12 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 lg:px-6 space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Как оформить покупку?</h1>
            <p class="text-slate-500 mt-2">
                Мы проанализировали цены и собрали магазины, где доступны все товары из вашего списка.
            </p>
            @if($storeName)
                <p class="text-sm text-slate-500 mt-1">
                    Вы выбрали магазин: <span class="font-medium text-slate-700">{{ $storeName }}</span>
                </p>
            @endif
        </div>

        @if (!empty($result['options']))
            <div class="space-y-5">
                @foreach ($result['options'] as $index => $option)
                    <div class="bg-white rounded-3xl border-2 {{ $index === 0 ? 'border-green-500' : 'border-transparent' }} shadow-sm p-6">
                        @if ($index === 0)
                            <span class="inline-flex items-center gap-2 rounded-full bg-green-500 px-4 py-1 text-xs font-semibold text-white mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Рекомендуем
                            </span>
                        @endif

                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ $option['store_name'] }}</h2>
                                <p class="text-sm text-slate-500 mt-1">Все товары доступны в этом магазине.</p>
                            </div>
                            <div class="text-left md:text-right">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Примерная сумма</p>
                                <p class="text-2xl font-semibold text-slate-900">
                                    ~ {{ number_format($option['total_estimated'] / 100, 2, ',', ' ') }} kr
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <form action="{{ route('orders.personal_shopper.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="store_id" value="{{ $option['store_id'] }}">

                                @foreach ($items as $i => $item)
                                    <input type="hidden" name="products[{{ $i }}][id]" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="products[{{ $i }}][quantity]" value="{{ $item['quantity'] }}">
                                @endforeach

                                <button
                                    type="submit"
                                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-white font-semibold hover:bg-blue-700 transition"
                                >
                                    Оформить заказ в этом магазине
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 5.25 21 12m0 0-7.5 6.75M21 12H3" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-8 shadow-sm">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">🤔</span>
                    <div>
                        <h2 class="text-xl font-semibold text-yellow-800">Не нашли подходящих вариантов</h2>
                        <p class="text-yellow-700 mt-2">
                            Ни один активный магазин не продаёт весь список товаров.
                            @if($storeName)
                                Выбранный магазин «{{ $storeName }}» не покрывает все позиции — попробуйте изменить список или выбрать другой магазин.
                            @else
                                Попробуйте уменьшить количество позиций или вернитесь позже.
                            @endif
                        </p>
                        <a href="{{ route('public.cart.index') }}" class="mt-4 inline-flex items-center gap-2 font-semibold text-yellow-800 hover:underline">
                            &larr; Вернуться к списку покупок
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

