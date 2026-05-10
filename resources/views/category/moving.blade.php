@extends('layouts.app')

@section('title', 'Переезд под ключ — GLF BiKube')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 text-white overflow-hidden">
        <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/50"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
            <div class="text-center">
                <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tight">
                    <span class="bg-gradient-to-r from-yellow-300 via-orange-300 to-yellow-300 bg-clip-text text-transparent">
                        Переезд под ключ
                    </span>
                </h1>
                <p class="text-xl md:text-3xl text-emerald-100 mb-4 font-medium">
                    Полный комплекс услуг для переезда в Норвегии
                </p>
                <p class="text-lg text-emerald-200 mb-10 max-w-3xl mx-auto">
                    Упаковка, погрузка/разгрузка, транспорт, сборка/разборка мебели, эко-вывоз ненужных вещей. 
                    Мы берём на себя всё — от планирования до финальной уборки.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('public.category', ['slug' => 'moving']) ?? route('account.new-order.index') ?? '#' }}"
                       class="group bg-white text-emerald-600 hover:bg-emerald-50 font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg flex items-center justify-center gap-2">
                        <svg class="w-6 h-6 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        📝 Запросить расчёт
                    </a>
                    <a href="#services" 
                       class="bg-emerald-500/90 hover:bg-emerald-400 text-white font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg">
                        Узнать больше
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        {{-- Services Section --}}
        @if(isset($services) && $services->count() > 0)
            <section id="services" class="mb-16">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-black text-slate-900 mb-4">Наши услуги</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Полный спектр услуг для комфортного переезда
                    </p>
                </div>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div class="group bg-white rounded-2xl shadow-lg border-2 border-slate-200 hover:border-emerald-400 hover:shadow-2xl transition-all p-6 transform hover:-translate-y-2">
                            <div class="text-4xl mb-4 transform group-hover:scale-110 transition-transform">📦</div>
                            <h3 class="text-xl font-black text-slate-900 mb-2 group-hover:text-emerald-600 transition">{{ $service->name }}</h3>
                            @if($service->description)
                                <p class="text-slate-600 leading-relaxed">{{ $service->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Packages Section --}}
        <section class="mb-16">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-black text-slate-900 mb-4">Примерные пакеты</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                    Выберите подходящий вариант для вашего переезда
                </p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                <div class="bg-gradient-to-br from-white to-emerald-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-emerald-400 hover:shadow-2xl transition-all p-8 transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">🏠</div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3">1-комнатная квартира</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed">Упаковка, погрузка, транспорт, разгрузка</p>
                    <ul class="space-y-2 text-sm text-slate-600 mb-6">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Профессиональная упаковка
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Безопасная транспортировка
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Аккуратная разгрузка
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-white to-emerald-50 rounded-2xl shadow-lg border-2 border-emerald-400 hover:shadow-2xl transition-all p-8 transform hover:-translate-y-2 relative">
                    <div class="absolute top-4 right-4 bg-emerald-600 text-white px-3 py-1 rounded-full text-xs font-bold">Популярно</div>
                    <div class="text-5xl mb-4">🏘️</div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3">2-комнатная квартира</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed">Полный комплекс + сборка мебели</p>
                    <ul class="space-y-2 text-sm text-slate-600 mb-6">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Всё из пакета "1-комнатная"
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Сборка/разборка мебели
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Дополнительная упаковка
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-white to-emerald-50 rounded-2xl shadow-lg border-2 border-slate-200 hover:border-emerald-400 hover:shadow-2xl transition-all p-8 transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">🏡</div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3">Дом</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed">Индивидуальный расчёт под ваш объём</p>
                    <ul class="space-y-2 text-sm text-slate-600 mb-6">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Персональный менеджер
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Гибкий график работ
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Все дополнительные услуги
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-3xl shadow-2xl p-12 text-center text-white">
            <h2 class="text-4xl font-black mb-4">Готовы начать переезд?</h2>
            <p class="text-xl text-emerald-100 mb-8 max-w-2xl mx-auto">
                Получите бесплатную консультацию и расчёт стоимости прямо сейчас
            </p>
            <a href="{{ route('public.category', ['slug' => 'moving']) ?? route('account.new-order.index') ?? '#' }}"
               class="inline-flex items-center gap-2 bg-white text-emerald-600 hover:bg-emerald-50 font-bold py-4 px-8 rounded-xl shadow-xl transition-all transform hover:-translate-y-1 text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Запросить расчёт
            </a>
        </section>
    </div>
</div>
@endsection
