@extends('layouts.app')

@section('title', 'Магазины для доставки продуктов — GLF BiKube')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Магазины для доставки продуктов</h1>
            <p class="text-gray-600 mt-2">Выберите магазин для заказа доставки продуктов</p>
        </div>

        @if($stores->isEmpty())
            <p class="text-gray-500">Магазины пока не добавлены.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($stores as $store)
                    <a href="{{ route('public.stores.show', $store->slug) }}"
                       class="block rounded-xl border border-gray-200 bg-white p-6 hover:shadow-lg transition">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $store->brand ?? $store->name }}</h3>
                        <p class="text-sm text-gray-500 mt-2">{{ $store->address ?? $store->city }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $stores->links() }}
            </div>
        @endif
    </div>
@endsection

