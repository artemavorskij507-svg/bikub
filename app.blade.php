<!DOCTYPE html>
<html lang="uk" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GLF Bikube — міські послуги в Нарвіку')</title>
    <meta name="description" content="@yield('description', 'Доставка покупок, переїзд «під ключ», майстер, еко-утилізація, кур\'єр — усе в одній платформі')">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Tailwind CDN fallback & Alpine.js -->
    <script>
    (function(){var id='tw-cdn-fallback';if(!document.getElementById(id)){
      var s=document.createElement('script');s.id=id;s.src="https://cdn.tailwindcss.com";document.head.appendChild(s);
    }})();
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 antialiased">
    <!-- Navigation -->
    <nav class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-slate-200/70 dark:border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="{{ route('public.home') }}" class="text-2xl font-bold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition">
                        <i class="fa-solid fa-truck mr-2"></i>GLF Bikube
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ route('public.home') }}" class="text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 transition">Головна</a>
                    <a href="{{ route('public.catalog.index') }}" class="text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 transition">Каталог</a>
                    <a href="/admin" class="bg-sky-600 text-white px-4 py-2 rounded-lg hover:bg-sky-700 transition">Адмін</a>
                </div>
                <div class="md:hidden">
                    <button type="button" class="text-slate-700 dark:text-slate-300" x-data="{ open: false }" @click="open = !open">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200/70 dark:border-white/10 bg-white/50 dark:bg-slate-900/50 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">GLF Bikube</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Міські послуги, які приїжджають до вас.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-slate-900 dark:text-white">Послуги</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li><a href="{{ route('public.catalog.index', ['category' => 'care']) }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Доставка</a></li>
                        <li><a href="{{ route('public.catalog.index', ['category' => 'eco']) }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Еко-утилізація</a></li>
                        <li><a href="{{ route('public.catalog.index', ['category' => 'master']) }}" class="hover:text-сky-600 dark:hover:text-sky-400 transition">Майстер</a></li>
                        <li><a href="{{ route('public.catalog.index', ['category' => 'tow']) }}" class="hover:text-сky-600 dark:hover:text-sky-400 transition">Переїзд</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-slate-900 dark:text-white">Підтримка</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li><a href="/admin" class="hover:text-sky-600 dark:hover:text-sky-400 transition">Адмін-панель</a></li>
                        <li><a href="/api-info" class="hover:text-sky-600 dark:hover:text-sky-400 transition">API</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-slate-900 dark:text-white">Контакти</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Доступно 24/7 для ваших потреб.</p>
                </div>
            </div>
            <div class="border-t border-slate-200/70 dark:border-white/10 mt-8 pt-8 text-center text-sm text-slate-500 dark:text-slate-400">
                <p>&copy; {{ date('Y') }} GLF Bikube. Всі права захищені.</p>
            </div>
        </div>
    </footer>
</body>
</html>
