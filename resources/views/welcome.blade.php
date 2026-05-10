<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BiKube - Ваш надёжный помощник для любой задачи</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Hexagon shape */
        .hexagon {
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }
        
        /* Neon glow effects */
        .neon-glow-cyan {
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.5), 0 0 40px rgba(6, 182, 212, 0.3);
        }
        
        .neon-glow-gold {
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.6), 0 0 60px rgba(245, 158, 11, 0.4);
        }
        
        .text-glow-gold {
            text-shadow: 0 0 20px rgba(245, 158, 11, 0.8), 0 0 40px rgba(245, 158, 11, 0.5);
        }
        
        /* Animated background particles */
        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            25% { transform: translateY(-20px) translateX(10px); }
            50% { transform: translateY(-10px) translateX(-10px); }
            75% { transform: translateY(-30px) translateX(5px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.8; }
        }
        
        @keyframes rotate-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .particle {
            animation: float 8s ease-in-out infinite;
        }
        
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        .rotate-slow {
            animation: rotate-slow 60s linear infinite;
        }
        
        /* Digital noise background */
        .digital-noise {
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }
        
        /* Connection lines */
        .connection-line {
            stroke: rgba(6, 182, 212, 0.5);
            stroke-width: 2;
            stroke-dasharray: 5, 5;
            animation: dash 20s linear infinite;
        }
        
        @keyframes dash {
            to {
                stroke-dashoffset: -100;
            }
        }
        
        /* Hover effects */
        .service-hex:hover {
            transform: scale(1.1);
            transition: all 0.3s ease;
        }
        
        .service-hex:hover .hexagon {
            border-color: rgba(6, 182, 212, 1);
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.8);
        }
    </style>
</head>
<body class="antialiased bg-slate-950 text-white overflow-x-hidden">
    
    <!-- Animated Background -->
    <div class="fixed inset-0 z-0 digital-noise">
        <!-- Floating particles -->
        <div class="absolute top-20 left-10 w-2 h-2 bg-cyan-400 rounded-full particle opacity-60" style="animation-delay: 0s;"></div>
        <div class="absolute top-40 right-20 w-3 h-3 bg-amber-400 rounded-full particle opacity-50" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-40 left-1/4 w-2 h-2 bg-purple-400 rounded-full particle opacity-70" style="animation-delay: 4s;"></div>
        <div class="absolute top-1/3 right-1/3 w-2 h-2 bg-cyan-300 rounded-full particle opacity-40" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-20 right-10 w-3 h-3 bg-amber-300 rounded-full particle opacity-60" style="animation-delay: 3s;"></div>
    </div>

    <!-- Header -->
    <header class="relative z-50 border-b border-slate-800/50 backdrop-blur-xl bg-slate-950/80">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-12 h-12 hexagon bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center neon-glow-gold">
                        <svg class="w-7 h-7 text-black" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-black bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">BiKube</span>
                </a>

                <!-- Navigation -->
                @if (Route::has('login'))
                    <nav class="flex items-center space-x-6">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-slate-300 hover:text-amber-400 transition-colors font-medium">Dashboard</a>
                        @else
                            <a href="/login" class="text-slate-300 hover:text-amber-400 transition-colors font-medium">Войти</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-bold px-6 py-2.5 rounded-lg transition-all neon-glow-gold">
                                    Регистрация
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative z-30 pt-20 pb-32 overflow-hidden">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Text Content -->
                <div class="space-y-8">
                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-black leading-tight">
                        <span class="block text-white mb-2">Р’Р°С€ СѓР»СЊСЏРЅС‹Р№ СЃРµСЂРІРёСЃ</span>
                        <span class="block bg-gradient-to-r from-amber-400 via-orange-500 to-amber-600 bg-clip-text text-transparent text-glow-gold">Ваш ульяный сервис Доставка, мастер на час, переезды, социальная помощь, эвакуатор и индивидуаль</span>
                    </h1>
                    
                    <p class="text-2xl text-slate-300 font-light">
                        От <span class="text-amber-400 font-medium">батонов</span> — BiKube решит всё!
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="#services" class="group bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-bold px-8 py-4 rounded-xl transition-all neon-glow-gold flex items-center justify-center">
                            <span>Заказать услугу</span>
                            <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                        <a href="#about" class="border-2 border-cyan-500 hover:bg-cyan-500/10 text-white font-bold px-8 py-4 rounded-xl transition-all neon-glow-cyan">
                            Узнать больше
                        </a>
                    </div>
                </div>

                <!-- Right: 3D Bees Illustration -->
                <div class="relative h-96 lg:h-[500px]">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <!-- Platform glow -->
                        <div class="absolute bottom-0 w-full h-32 bg-gradient-to-t from-amber-500/30 to-transparent blur-3xl"></div>
                        
                        <!-- Bee 1 (Left) -->
                        <div class="absolute left-10 top-20 animate-bounce" style="animation-duration: 3s; animation-delay: 0s;">
                            <div class="w-24 h-24 hexagon bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center neon-glow-gold">
                                <span class="text-4xl">🐝</span>
                            </div>
                            <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-16 h-16 bg-slate-800 rounded-lg border-2 border-amber-400 flex items-center justify-center text-2xl">
                                📦
                            </div>
                        </div>

                        <!-- Bee 2 (Center) -->
                        <div class="absolute left-1/2 -translate-x-1/2 top-10 animate-bounce" style="animation-duration: 2.5s; animation-delay: 0.5s;">
                            <div class="w-32 h-32 hexagon bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center neon-glow-cyan">
                                <span class="text-5xl">🐝</span>
                            </div>
                            <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 w-20 h-20 bg-slate-800 rounded-lg border-2 border-cyan-400 flex items-center justify-center text-3xl">
                                🎁
                            </div>
                        </div>

                        <!-- Bee 3 (Right) -->
                        <div class="absolute right-10 top-32 animate-bounce" style="animation-duration: 3.5s; animation-delay: 1s;">
                            <div class="w-24 h-24 hexagon bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center" style="box-shadow: 0 0 20px rgba(168, 85, 247, 0.5);">
                                <span class="text-4xl">🐝</span>
                            </div>
                            <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-16 h-16 bg-slate-800 rounded-lg border-2 border-purple-400 flex items-center justify-center text-2xl">
                                📮
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section (Hive Web) -->
    <section id="services" class="relative z-30 py-20 bg-gradient-to-b from-transparent to-slate-900/50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4">
                    <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">Наши услуги</span>
                </h2>
                <p class="text-slate-400 text-lg">Комплексные решения для вашего бизнеса и повседневной жизни</p>
            </div>

            <!-- Hive Grid -->
            <div class="relative max-w-5xl mx-auto">
                <!-- SVG Connection Lines -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 800 600" style="z-index: 1;">
                    <!-- Lines from center to each service -->
                    <line x1="400" y1="300" x2="200" y2="150" class="connection-line" />
                    <line x1="400" y1="300" x2="600" y2="150" class="connection-line" />
                    <line x1="400" y1="300" x2="150" y2="300" class="connection-line" />
                    <line x1="400" y1="300" x2="650" y2="300" class="connection-line" />
                    <line x1="400" y1="300" x2="200" y2="450" class="connection-line" />
                    <line x1="400" y1="300" x2="600" y2="450" class="connection-line" />
                </svg>

                <!-- Services positioned in orbit -->
                <div class="relative h-[600px]">
                    <!-- Center: Holographic Hive -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20">
                        <div class="w-40 h-40 hexagon bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 flex items-center justify-center neon-glow-gold rotate-slow border-4 border-amber-300/50">
                            <svg class="w-20 h-20 text-black" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                            </svg>
                        </div>
                        <div class="absolute inset-0 hexagon border-2 border-amber-400/30 pulse-glow"></div>
                    </div>

                    <!-- Service 1: Delivery (Top Left) -->
                    <a href="/category/delivery" class="service-hex absolute top-0 left-1/4 -translate-x-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-cyan-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">🚁</div>
                            <span class="text-xs font-bold text-cyan-400">Доставка</span>
                        </div>
                    </a>

                    <!-- Service 2: Eco (Top Right) -->
                    <a href="/category/eco" class="service-hex absolute top-0 right-1/4 -translate-x-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-emerald-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">♻️</div>
                            <span class="text-xs font-bold text-emerald-400">Переработка</span>
                        </div>
                    </a>

                    <!-- Service 3: Care (Middle Left) -->
                    <a href="/category/social-help" class="service-hex absolute top-1/2 left-0 -translate-y-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-pink-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">🏥</div>
                            <span class="text-xs font-bold text-pink-400">Уход</span>
                        </div>
                    </a>

                    <!-- Service 4: Store (Middle Right) -->
                    <a href="/category/delivery" class="service-hex absolute top-1/2 right-0 -translate-y-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-purple-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">🛒</div>
                            <span class="text-xs font-bold text-purple-400">Магазин</span>
                        </div>
                    </a>

                    <!-- Service 5: Moving (Bottom Left) -->
                    <a href="/category/moving" class="service-hex absolute bottom-0 left-1/4 -translate-x-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-indigo-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">🚚</div>
                            <span class="text-xs font-bold text-indigo-400">Переезды</span>
                        </div>
                    </a>

                    <!-- Service 6: Master (Bottom Right) -->
                    <a href="/category/handyman" class="service-hex absolute bottom-0 right-1/4 -translate-x-1/2 z-10 group">
                        <div class="w-28 h-28 hexagon bg-slate-800/90 backdrop-blur border-2 border-amber-500/50 flex flex-col items-center justify-center p-4 transition-all">
                            <div class="text-4xl mb-1">🔧</div>
                            <span class="text-xs font-bold text-amber-400">Мастер</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative z-30 bg-slate-950 border-t border-slate-800/50 pt-16 pb-8 mt-20">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <!-- Logo -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 hexagon bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center neon-glow-gold">
                            <svg class="w-7 h-7 text-black" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                            </svg>
                        </div>
                        <span class="text-2xl font-black bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">BiKube</span>
                    </div>
                    <p class="text-slate-400 leading-relaxed">
                        Инновационная платформа для управления городскими услугами в Narvik, Norway.
                    </p>
                </div>

                <!-- Contacts -->
                <div>
                    <h4 class="text-white font-bold mb-4">Контакты</h4>
                    <ul class="space-y-3 text-slate-400">
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Narvik, Norway</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>support@bikube.no</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span>+47 123 45 678</span>
                        </li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div>
                    <h4 class="text-white font-bold mb-4">Социальные сети</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="w-12 h-12 hexagon bg-slate-800 hover:bg-cyan-500/20 border-2 border-cyan-500/50 flex items-center justify-center transition-all neon-glow-cyan">
                            <svg class="w-6 h-6 text-cyan-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-12 h-12 hexagon bg-slate-800 hover:bg-cyan-500/20 border-2 border-cyan-500/50 flex items-center justify-center transition-all neon-glow-cyan">
                            <svg class="w-6 h-6 text-cyan-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-12 h-12 hexagon bg-slate-800 hover:bg-cyan-500/20 border-2 border-cyan-500/50 flex items-center justify-center transition-all neon-glow-cyan">
                            <svg class="w-6 h-6 text-cyan-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-slate-800 pt-8 text-center text-slate-500 text-sm">
                <p>&copy; {{ date('Y') }} BiKube. Все права защищены. Инновационные решения для городской среды.</p>
            </div>
        </div>
    </footer>

</body>
</html>
