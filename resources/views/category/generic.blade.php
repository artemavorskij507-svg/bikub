@extends('layouts.app')

@section('title', $category->name . ' — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                {{ $category->name }}
            </h1>
            @if($category->description)
                <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                    {{ $category->description }}
                </p>
            @endif
        </header>

        {{-- Блок: Услуги --}}
        @if(isset($services) && $services->count() > 0)
            <section class="space-y-3">
                <h2 class="text-xl font-semibold">Доступные услуги</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div class="border border-gray-100 rounded-xl p-4 bg-white/70 hover:border-emerald-400 hover:shadow-sm transition">
                            <p class="text-sm font-semibold">{{ $service->name }}</p>
                            @if($service->description)
                                <p class="text-xs text-gray-500 mt-1">{{ $service->description }}</p>
                            @endif
                            <a href="{{ route('public.service', $service->slug ?? $service->id) }}"
                               class="mt-3 inline-flex text-xs font-medium text-emerald-600 hover:text-emerald-700">
                                Подробнее →
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <p class="text-xs text-gray-500">Услуги для этой категории ещё не добавлены.</p>
        @endif
    </section>
@endsection

