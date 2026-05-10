@extends('layouts.app')

@section('title', 'Bikube — Ваш ульяный сервис')

@section('content')
<div class="relative min-h-screen bg-gradient-to-b from-[#0A0F2B] via-[#1E1B4B] to-[#0A0F2B] overflow-hidden">
    
    {{-- Animated Grid Background --}}
    <div class="absolute inset-0 opacity-20">
        <div class="grid-lines"></div>
    </div>

    {{-- Floating Particles --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < 30; $i++)
            <div class="particle-neon" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 10) }}s; animation-duration: {{ rand(20, 40) }}s;"></div>
        @endfor
    </div>

    {{-- Navigation with Glassmorphism --}}
    <nav class="relative z-50 px-8 lg:px-32 py-6 flex items-center justify-between backdrop-blur-xl bg-[#0A0F2B]/60 border-b border-[#3B3B6D]/50">
        <div class="flex items-center gap-3">
            <div class="w-14 h-14 bg-gradient-to-br from-yellow-400 via-orange-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-[0_0_30px_rgba(255,215,0,0.5)] animate-pulse-slow overflow-visible">
                <img src="{{ asset('images/bikube.png') }}" alt="Bikube Logo" class="w-28 h-28 object-contain">
            </div>
            <span class="text-3xl font-black text-white tracking-tight bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">
                Bikube
            </span>
        </div>
        <div class="hidden md:flex items-center gap-8">
            <a href="#services" class="text-gray-300 hover:text-yellow-400 font-bold transition-all duration-300 hover:scale-110">Услуги</a>
            <a href="#how-it-works" class="text-gray-300 hover:text-yellow-400 font-bold transition-all duration-300 hover:scale-110">Как работает</a>
            <a href="#contact" class="text-gray-300 hover:text-yellow-400 font-bold transition-all duration-300 hover:scale-110">Контакты</a>
        </div>
        <div class="flex items-center gap-4">
            @auth
                <a href="{{ route('lk.dashboard') }}" class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-black font-black rounded-xl shadow-[0_0_30px_rgba(255,215,0,0.5)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_50px_rgba(255,215,0,0.8)]">
                    Личный кабинет
                </a>
            @else
                <a href="{{ route('login') }}" class="px-6 py-3 text-gray-300 hover:text-yellow-400 font-bold transition-colors">Войти</a>
                <a href="{{ route('register') }}" class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-black font-black rounded-xl shadow-[0_0_30px_rgba(255,215,0,0.5)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_50px_rgba(255,215,0,0.8)]">
                    Регистрация
                </a>
            @endauth
        </div>
    </nav>

    {{-- Hero Section with Glowing Hive Effect --}}
    <section class="relative z-10 flex flex-col lg:flex-row items-center justify-between px-8 lg:px-32 pt-24 pb-32 gap-16">
        <div class="flex-1 text-white max-w-2xl relative">
            {{-- Glowing Background Effect --}}
            <div class="absolute -inset-10 bg-gradient-to-r from-yellow-500/20 via-orange-500/20 to-yellow-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center gap-3 px-5 py-2.5 backdrop-blur-xl bg-[#1E1B4B]/60 border border-yellow-500/30 rounded-full mb-8 shadow-[0_0_20px_rgba(255,215,0,0.3)]">
                    <span class="w-3 h-3 bg-yellow-400 rounded-full animate-pulse shadow-[0_0_10px_rgba(255,215,0,0.8)]"></span>
                    <span class="text-sm font-black text-yellow-400 uppercase tracking-wider">Доступно 24/7 в Narvik</span>
                </div>
                
                <h1 class="text-6xl lg:text-8xl font-black tracking-tight mb-8 leading-tight">
                    <span class="bg-gradient-to-r from-white via-yellow-200 to-orange-200 bg-clip-text text-transparent drop-shadow-[0_0_30px_rgba(255,215,0,0.5)]">
                        Bikube
                    </span>
                    <br>
                    <span class="text-4xl lg:text-5xl text-gray-200 font-bold">
                        Ваш ульяный сервис
                    </span>
                </h1>
                
                <p class="text-xl lg:text-2xl text-gray-300 mb-10 leading-relaxed font-medium">
                    Доставка, мастер на час, переезды, социальная помощь, эвакуатор и индивидуальные поручения. 
                    <span class="text-yellow-400 font-black">Как пчёлы — быстро, надежно и эффективно.</span>
                </p>
                
                <div class="flex flex-wrap gap-4">
                    <a href="#services" class="group relative px-10 py-5 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-black font-black text-lg rounded-2xl shadow-[0_0_40px_rgba(255,215,0,0.6)] hover:shadow-[0_0_60px_rgba(255,215,0,0.8)] transition-all duration-500 hover:scale-110 flex items-center gap-3 overflow-hidden">
                        <span class="relative z-10">Выбрать услугу</span>
                        <svg class="w-6 h-6 relative z-10 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        <div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-orange-400 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    </a>
                    <a href="#how-it-works" class="px-10 py-5 backdrop-blur-xl bg-[#1E1B4B]/60 hover:bg-[#1E1B4B]/80 text-white font-black text-lg rounded-2xl border-2 border-yellow-500/30 hover:border-yellow-500/50 shadow-[0_0_20px_rgba(255,215,0,0.2)] hover:shadow-[0_0_30px_rgba(255,215,0,0.4)] transition-all duration-300 hover:scale-105">
                        Как это работает
                    </a>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-8 mt-16">
                    <div class="text-center backdrop-blur-xl bg-[#1E1B4B]/40 rounded-2xl p-6 border border-yellow-500/20 shadow-[0_0_20px_rgba(255,215,0,0.1)]">
                        <div class="text-4xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_10px_rgba(255,215,0,0.5)]">500+</div>
                        <div class="text-sm text-gray-400 font-bold uppercase tracking-wider">Заказов в день</div>
                    </div>
                    <div class="text-center backdrop-blur-xl bg-[#1E1B4B]/40 rounded-2xl p-6 border border-yellow-500/20 shadow-[0_0_20px_rgba(255,215,0,0.1)]">
                        <div class="text-4xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_10px_rgba(255,215,0,0.5)]">4.9★</div>
                        <div class="text-sm text-gray-400 font-bold uppercase tracking-wider">Средний рейтинг</div>
                    </div>
                    <div class="text-center backdrop-blur-xl bg-[#1E1B4B]/40 rounded-2xl p-6 border border-yellow-500/20 shadow-[0_0_20px_rgba(255,215,0,0.1)]">
                        <div class="text-4xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_10px_rgba(255,215,0,0.5)]">24/7</div>
                        <div class="text-sm text-gray-400 font-bold uppercase tracking-wider">Поддержка</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Brand Logo Instead of 3D Hive --}}
        <div class="flex-1 relative w-full max-w-2xl flex items-center justify-center">
            <div class="relative w-80 h-80 md:w-full md:h-full animate-float flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/40 via-orange-500/40 to-yellow-500/40 rounded-full blur-3xl animate-pulse-slow"></div>
                <img src="{{ asset('images/logo.png') }}" alt="Bikube" class="w-64 h-64 md:w-full md:h-auto max-w-xl object-contain relative z-10">
            </div>
        </div>
    </section>

    {{-- Services Section with Hexagon Menu --}}
    <section id="services" class="relative z-10 px-8 lg:px-32 py-24">
        <div class="text-center mb-20">
            <h2 class="text-5xl lg:text-6xl font-black text-white mb-6 bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent drop-shadow-[0_0_30px_rgba(255,215,0,0.5)]">
                Наши услуги
            </h2>
            <p class="text-xl text-gray-300 font-medium backdrop-blur-xl bg-[#1E1B4B]/40 rounded-full px-6 py-3 inline-block border border-yellow-500/20">
                Всё, что вам нужно — в одном месте
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8 justify-items-center">
            @php
                $services = [
                    ['title' => 'Доставка', 'icon' => '🛒', 'slug' => 'delivery', 'color' => 'from-blue-500 to-cyan-500'],
                    ['title' => 'Переезд под ключ', 'icon' => '🏠', 'slug' => 'moving', 'color' => 'from-green-500 to-emerald-500'],
                    ['title' => 'Мастер на час', 'icon' => '🔧', 'slug' => 'handyman', 'color' => 'from-yellow-500 to-orange-500'],
                    ['title' => 'Эко-утилизация', 'icon' => '♻️', 'slug' => 'eco', 'color' => 'from-purple-500 to-pink-500'],
                    // Новые slug для социальных и roadside‑услуг, чтобы не падать в /catalog
                    ['title' => 'Социальная помощь', 'icon' => '👴', 'slug' => 'social-help', 'color' => 'from-rose-500 to-pink-500'],
                    ['title' => 'Индивидуальный помощник', 'icon' => '🎯', 'slug' => 'personal-task', 'color' => 'from-amber-500 to-yellow-500'],
                    ['title' => 'Эвакуатор', 'icon' => '🚑', 'slug' => 'tow', 'color' => 'from-red-500 to-orange-500'],
                    ['title' => 'Доска объявлений', 'icon' => '📋', 'slug' => 'classifieds', 'color' => 'from-sky-500 to-indigo-500'],
                    ['title' => 'IT & Маркетинг', 'icon' => '💻', 'slug' => 'it', 'color' => 'from-indigo-500 to-blue-500'],
                    ['title' => 'GLF Mat', 'icon' => '🍔', 'slug' => 'food', 'color' => 'from-orange-500 to-red-500'],


                ];
            @endphp

            @foreach($services as $service)
                @php
                    $href = ($service['slug'] ?? null) === 'classifieds'
                        ? route('public.category.classifieds')
                        : route('public.category', $service['slug']);
                @endphp
                <a href="{{ $href }}" class="hexagon-service group">
                    <div class="hexagon-inner-service bg-gradient-to-br {{ $service['color'] }} backdrop-blur-xl border-2 border-white/20 group-hover:border-white/50 transition-all duration-500 flex flex-col items-center justify-center p-6 shadow-[0_0_30px_rgba(255,215,0,0.2)] group-hover:shadow-[0_0_50px_rgba(255,215,0,0.6)] transform group-hover:scale-110 group-hover:-translate-y-2">
                        <span class="text-5xl mb-4 transform group-hover:rotate-12 transition-transform duration-500">{{ $service['icon'] }}</span>
                        <span class="text-white font-black text-center text-sm leading-tight">{{ $service['title'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- How It Works --}}
    <section id="how-it-works" class="relative z-10 px-8 lg:px-32 py-24 backdrop-blur-xl bg-[#1E1B4B]/30 border-y border-[#3B3B6D]/50">
        <div class="text-center mb-20">
            <h2 class="text-5xl lg:text-6xl font-black text-white mb-6 bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent">
                Как это работает
            </h2>
            <p class="text-xl text-gray-300 font-medium">Три простых шага до выполнения задачи</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            @php
                $steps = [
                    ['num' => '1', 'title' => 'Выберите услугу', 'desc' => 'Найдите нужную услугу из нашего каталога', 'icon' => '🎯', 'color' => 'from-blue-500 to-cyan-500'],
                    ['num' => '2', 'title' => 'Оформите заказ', 'desc' => 'Укажите детали и выберите удобное время', 'icon' => '📝', 'color' => 'from-purple-500 to-pink-500'],
                    ['num' => '3', 'title' => 'Получите результат', 'desc' => 'Наши специалисты выполнят задачу быстро и качественно', 'icon' => '✅', 'color' => 'from-green-500 to-emerald-500'],
                ];
            @endphp

            @foreach($steps as $step)
                <div class="relative text-center group">
                    <div class="absolute -inset-4 bg-gradient-to-br {{ $step['color'] }} rounded-3xl blur-2xl opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="relative backdrop-blur-xl bg-[#1E1B4B]/60 rounded-3xl p-10 border-2 border-yellow-500/20 group-hover:border-yellow-500/50 shadow-[0_0_30px_rgba(255,215,0,0.2)] group-hover:shadow-[0_0_50px_rgba(255,215,0,0.4)] transition-all duration-500">
                        <div class="w-28 h-28 mx-auto mb-8 bg-gradient-to-br {{ $step['color'] }} rounded-full flex items-center justify-center text-5xl shadow-[0_0_40px_rgba(255,215,0,0.4)] group-hover:scale-110 group-hover:rotate-12 transition-all duration-500">
                            {{ $step['icon'] }}
                        </div>
                        <div class="absolute top-8 left-1/2 -translate-x-1/2 w-16 h-16 bg-[#0A0F2B] rounded-full flex items-center justify-center text-2xl font-black text-yellow-400 border-4 border-yellow-500/50 shadow-[0_0_20px_rgba(255,215,0,0.5)]">
                            {{ $step['num'] }}
                        </div>
                        <h3 class="text-3xl font-black text-white mb-4 mt-12">{{ $step['title'] }}</h3>
                        <p class="text-gray-300 font-medium leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="relative z-10 px-8 lg:px-32 py-24">
        <div class="relative bg-gradient-to-br from-yellow-500 via-orange-500 to-yellow-500 rounded-3xl p-16 text-center overflow-hidden shadow-[0_0_80px_rgba(255,215,0,0.6)]">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiMwMDAwMDAiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00em0wLTEwYzAtMi4yMS0xLjc5LTQtNC00cy00IDEuNzktNCA0IDEuNzkgNCA0IDQgNC0xLjc5IDQtNHptMC0xMGMwLTIuMjEtMS43OS00LTQtNHMtNCAxLjc5LTQgNCAxLjc5IDQgNCA0IDQtMS43OSA0LTR6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
            
            <div class="relative z-10">
                <h2 class="text-5xl lg:text-6xl font-black text-black mb-8 drop-shadow-2xl">Готовы начать?</h2>
                <p class="text-2xl text-black/80 mb-12 max-w-2xl mx-auto font-bold">Присоединяйтесь к тысячам довольных клиентов в Narvik</p>
                <a href="{{ route('register') }}" class="inline-block px-16 py-6 bg-black hover:bg-gray-900 text-yellow-400 font-black text-2xl rounded-2xl shadow-[0_0_60px_rgba(0,0,0,0.8)] hover:shadow-[0_0_80px_rgba(0,0,0,1)] transition-all duration-300 hover:scale-110 border-4 border-black/50">
                    Зарегистрироваться бесплатно
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 px-8 lg:px-32 py-16 backdrop-blur-xl bg-[#0A0F2B]/80 border-t border-[#3B3B6D]/50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(255,215,0,0.4)] overflow-hidden">
                        <img src="{{ asset('images/bikube.png') }}" alt="Bikube Logo" class="w-full h-full object-contain p-1">
                    </div>
                    <span class="text-2xl font-black text-white">Bikube</span>
                </div>
                <p class="text-gray-400 font-medium">Ваш надёжный помощник для любой задачи в Narvik</p>
            </div>
            
            <div>
                <h4 class="text-white font-black mb-6 text-lg">Услуги</h4>
                <ul class="space-y-3 text-gray-400 font-medium">
                    <li><a href="{{ route('public.category', 'delivery') }}" class="hover:text-yellow-400 transition-colors">Доставка</a></li>
                    <li><a href="{{ route('public.category', 'moving') }}" class="hover:text-yellow-400 transition-colors">Переезды</a></li>
                    <li><a href="{{ route('public.category', 'handyman') }}" class="hover:text-yellow-400 transition-colors">Мастер на час</a></li>
                    <li><a href="{{ route('public.category', 'tow') }}" class="hover:text-yellow-400 transition-colors">Эвакуатор</a></li>
                    <li><a href="{{ route('classifieds.index') }}" class="hover:text-yellow-400 transition-colors">Доска объявлений</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-white font-black mb-6 text-lg">Компания</h4>
                <ul class="space-y-3 text-gray-400 font-medium">
                    <li><a href="#" class="hover:text-yellow-400 transition-colors">О нас</a></li>
                    <li><a href="#" class="hover:text-yellow-400 transition-colors">Вакансии</a></li>
                    <li><a href="#" class="hover:text-yellow-400 transition-colors">Блог</a></li>
                    <li><a href="#" class="hover:text-yellow-400 transition-colors">Контакты</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="text-white font-black mb-6 text-lg">Контакты</h4>
                <ul class="space-y-3 text-gray-400 font-medium">
                    <li>📍 Narvik, Norway</li>
                    <li>📞 +47 000 00 000</li>
                    <li>✉️ info@bikube.no</li>
                </ul>
            </div>
        </div>
        
        <div class="pt-8 border-t border-[#3B3B6D] flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-gray-500 font-medium">© 2024 Bikube. Все права защищены.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="text-gray-400 hover:text-yellow-400 transition-colors font-medium">Политика конфиденциальности</a>
                <a href="#" class="text-gray-400 hover:text-yellow-400 transition-colors font-medium">Условия использования</a>
            </div>
        </div>
    </footer>
</div>

<style>
/* Grid Lines Background */
.grid-lines {
    background-image: 
        linear-gradient(rgba(255,215,0,0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,215,0,0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    width: 100%;
    height: 100%;
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50px, 50px); }
}

/* Neon Particles */
.particle-neon {
    position: absolute;
    width: 6px;
    height: 6px;
    background: radial-gradient(circle, rgba(255,215,0,0.8), rgba(255,165,0,0.4));
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(255,215,0,0.8), 0 0 20px rgba(255,215,0,0.4);
    animation: floatParticle linear infinite;
}

@keyframes floatParticle {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translate(100vw, -100vh) scale(0);
        opacity: 0;
    }
}

/* Float Animation */
.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

/* 3D Hexagon */
.hexagon-3d {
    aspect-ratio: 1;
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
    perspective: 1000px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px) rotateX(20deg);
    }
    to {
        opacity: 1;
        transform: translateY(0) rotateX(0deg);
    }
}

.hexagon-inner-3d {
    width: 100%;
    height: 100%;
    border-radius: 1rem;
    clip-path: polygon(25% 5%, 75% 5%, 100% 50%, 75% 95%, 25% 95%, 0% 50%);
    transform-style: preserve-3d;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.hexagon-3d:hover .hexagon-inner-3d {
    transform: translateZ(20px) rotateY(15deg);
}

/* Service Hexagons */
.hexagon-service {
    width: 180px;
    height: 180px;
    cursor: pointer;
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
    perspective: 1000px;
}

.hexagon-inner-service {
    width: 100%;
    height: 100%;
    clip-path: polygon(25% 5%, 75% 5%, 100% 50%, 75% 95%, 25% 95%, 0% 50%);
    transform-style: preserve-3d;
}

.hexagon-service:hover .hexagon-inner-service {
    transform: translateZ(30px) rotateY(10deg) rotateX(5deg);
}

/* Pulse Slow */
.animate-pulse-slow {
    animation: pulseSlow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulseSlow {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.05);
    }
}

/* Smooth Scroll */
html {
    scroll-behavior: smooth;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #0A0F2B;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #FFD700, #FF8C00);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #FFA500, #FF6347);
}
</style>
@endsection
