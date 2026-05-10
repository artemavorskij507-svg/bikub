@php
    $user = auth()->user();
    $navItems = \App\Support\Lk\Navigation::forUser($user);
    $navGlyphs = [
        'dashboard' => '🏠',
        'orders' => '📦',
        'executor_jobs' => '🛠️',
        'roadside_jobs' => '🚚',
        'schedule' => '🗓️',
        'wallet' => '💳',
        'notifications' => '🔔',
        'support' => '💬',
        'settings' => '⚙️',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Личный кабинет') — Bikube</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $appCssFile = $manifest['resources/css/app.css']['file'] ?? null;
        $appJsFile = $manifest['resources/js/app.js']['file'] ?? null;
    @endphp
    @if ($appCssFile)
        <link rel="stylesheet" href="/build/{{ $appCssFile }}">
    @endif
    @if ($appJsFile)
        <script type="module" src="/build/{{ $appJsFile }}"></script>
    @endif
    <link rel="stylesheet" href="/css/bikube-assistant.css">
    <style>
        [x-cloak] { display: none !important; }
        .lk-shell {
            min-height: 100vh;
            background: radial-gradient(1200px 600px at 80% -100px, rgba(251, 191, 36, 0.18), transparent 45%),
                        radial-gradient(1000px 600px at -10% -120px, rgba(14, 165, 233, 0.16), transparent 45%),
                        #f1f5f9;
        }
        .lk-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            border-radius: 1rem;
        }
        .lk-nav-link {
            display: flex;
            align-items: center;
            gap: .625rem;
            padding: .75rem .875rem;
            border-radius: .75rem;
            font-weight: 700;
            color: #334155;
            transition: all .2s ease;
            border: 1px solid transparent;
        }
        .lk-nav-link:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, .8);
            border-color: #e2e8f0;
        }
        .lk-nav-link.active {
            color: #0f172a;
            background: linear-gradient(135deg, #fef3c7, #ffedd5);
            border-color: #fdba74;
        }
        .lk-badge {
            margin-left: auto;
            padding: .1rem .45rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            color: #fff;
            background: #ef4444;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-amber-500 focus:text-white focus:rounded-lg focus:font-bold">
        Перейти к основному содержимому
    </a>

    <div x-data="{ mobileMenuOpen: false }" class="lk-shell">
        <header class="sticky top-0 z-40 border-b border-slate-200/80 backdrop-blur-md bg-white/85">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" class="lg:hidden inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white text-slate-700" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Открыть меню">
                            ☰
                        </button>
                        <a href="{{ route('lk.dashboard') }}" class="inline-flex items-center gap-2 font-black text-lg">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white">B</span>
                            <span>Bikube LK</span>
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('lk.notifications') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900">
                            🔔 Уведомления
                        </a>
                        <a href="{{ route('lk.profile') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900">
                            👤 {{ $user?->name ?? 'Пользователь' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white px-3 py-2 text-sm font-semibold hover:bg-black">
                                Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] gap-6">
                <aside class="hidden lg:block">
                    <div class="lk-card p-3 sticky top-24">
                        <nav class="space-y-1.5" aria-label="Навигация личного кабинета">
                            @foreach ($navItems as $item)
                                @continue(!($item['visible'] ?? false))
                                @php
                                    $isActive = request()->routeIs($item['route']);
                                    $routeUrl = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#';
                                @endphp
                                <a href="{{ $routeUrl }}" class="lk-nav-link {{ $isActive ? 'active' : '' }}" @if ($isActive) aria-current="page" @endif>
                                    <span aria-hidden="true">{{ $navGlyphs[$item['key']] ?? '•' }}</span>
                                    <span>{{ $item['label'] }}</span>
                                    @if (!empty($item['badge']))
                                        <span class="lk-badge">{{ (int) $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <div>
                    <aside x-show="mobileMenuOpen" x-transition x-cloak class="mb-4 lg:hidden lk-card p-3">
                        <nav class="space-y-1.5" aria-label="Мобильная навигация личного кабинета">
                            @foreach ($navItems as $item)
                                @continue(!($item['visible'] ?? false))
                                @php
                                    $isActive = request()->routeIs($item['route']);
                                    $routeUrl = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#';
                                @endphp
                                <a href="{{ $routeUrl }}" class="lk-nav-link {{ $isActive ? 'active' : '' }}">
                                    <span aria-hidden="true">{{ $navGlyphs[$item['key']] ?? '•' }}</span>
                                    <span>{{ $item['label'] }}</span>
                                    @if (!empty($item['badge']))
                                        <span class="lk-badge">{{ (int) $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </aside>

                    <main id="main-content">
                        @if(session('status'))
                            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                                {{ session('status') }}
                            </div>
                        @endif
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
    </div>

    @if (\Illuminate\Support\Facades\Route::has('lk.assistant.send'))
        @includeIf('lk.partials.assistant')
    @endif

    @stack('scripts')
</body>
</html>
