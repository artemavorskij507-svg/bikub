@extends('layouts.app')

@section('title', 'Рестораны и кафе — GLF BiKube')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Рестораны и кафе</h1>
            <p class="text-gray-600 mt-2">Выберите ресторан для заказа доставки еды</p>
        </div>

        @if($restaurants->isEmpty())
            <p class="text-gray-500">Рестораны пока не добавлены.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($restaurants as $restaurant)
                    <a href="{{ route('public.restaurants.show', $restaurant->slug) }}"
                       class="block rounded-xl border border-gray-200 bg-white p-6 hover:shadow-lg transition">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $restaurant->name }}</h3>
                        <p class="text-sm text-gray-500 mt-2">
                            {{ $restaurant->cuisine_type ? ucfirst($restaurant->cuisine_type) : 'Ресторан' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ $restaurant->address ?? $restaurant->city }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $restaurants->links() }}
            </div>
        @endif
    </div>
@endsection

