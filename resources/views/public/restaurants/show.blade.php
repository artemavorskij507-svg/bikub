@extends('layouts.app')

@section('title', $restaurant->name . ' — GLF BiKube')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ $restaurant->name }}</h1>
            <p class="text-gray-600 mt-2">
                {{ $restaurant->cuisine_type ? ucfirst($restaurant->cuisine_type) : 'Ресторан' }}
                @if($restaurant->address)
                    • {{ $restaurant->address }}
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <a href="{{ route('public.category', ['slug' => 'delivery']) }}?restaurant={{ $restaurant->slug }}"
               class="inline-flex items-center px-6 py-3 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                Заказать доставку из этого ресторана
            </a>
        </div>
    </div>
@endsection

