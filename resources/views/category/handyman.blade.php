@extends('layouts.app')

@section('title', 'Мастер на час — GLF BiKube')

@section('content')
    <section class="max-w-6xl mx-auto py-10 space-y-8">
        <header class="space-y-3">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">GLF BiKube</p>
            <h1 class="text-3xl md:text-4xl font-semibold">
                Мастер на час в Нарвике
            </h1>
            <p class="text-sm md:text-base text-gray-600 max-w-3xl">
                Сантехника, электрика, мебель, мелкий ремонт — квалифицированные мастера готовы помочь в удобное для тебя время.
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                @auth
                    <a href="{{ route('handyman.index') }}"
                       class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                        Вызвать мастера
                    </a>
                @else
                    <a href="{{ route('account.new-order.handyman') ?? route('login') }}"
                       class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                        Вызвать мастера
                    </a>
                @endauth
            </div>
        </header>

        {{-- Блок: Услуги --}}
        @if(isset($services) && $services->count() > 0)
            <section class="space-y-3">
                <h2 class="text-xl font-semibold">Услуги мастера</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div class="border border-gray-100 rounded-xl p-4 bg-white/70 hover:border-emerald-400 hover:shadow-sm transition">
                            <p class="text-sm font-semibold">{{ $service->name }}</p>
                            @if($service->description)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $service->description }}</p>
                            @endif
                            @if($service->base_rate_minor)
                                <p class="text-xs font-medium text-emerald-600 mt-2">
                                    От {{ number_format($service->base_rate_minor / 100, 0) }} NOK
                                </p>
                            @endif
                            @auth
                                <a href="{{ route('handyman.service.show', $service->slug ?? $service->id) }}"
                                   class="mt-3 inline-flex text-xs font-medium text-emerald-600 hover:text-emerald-700">
                                    Заказать →
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="mt-3 inline-flex text-xs font-medium text-emerald-600 hover:text-emerald-700">
                                    Заказать →
                                </a>
                            @endauth
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500">Услуги мастера ещё не добавлены.</p>
            @endif
        </section>
    </section>
@endsection

