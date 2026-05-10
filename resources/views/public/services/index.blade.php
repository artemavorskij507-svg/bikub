@extends('layouts.app')

@section('title', 'Все услуги — GLF BiKube')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Все услуги</h1>
            <p class="text-gray-600 mt-2">Выберите категорию услуги</p>
        </div>

        @if($categories->isEmpty())
            <p class="text-gray-500">Категории услуг пока не настроены.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <a href="{{ route('public.category', ['slug' => $category->slug ?? $category->code]) }}"
                       class="block rounded-xl border border-gray-200 bg-white p-6 hover:shadow-lg transition">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $category->name }}</h3>
                        @if($category->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-3">{{ $category->description }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

