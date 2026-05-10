<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Кабинет мастера') — GLF Bikube</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50">
<div class="min-h-screen flex flex-col md:flex-row">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-white border-r border-slate-200">
        <div class="p-4 border-b border-slate-200">
            <a href="{{ route('home') }}" class="font-bold text-xl text-slate-900">
                GLF <span class="text-primary-600">Bikube</span>
            </a>
        </div>
        <nav class="px-4 py-4 space-y-1">
            <a href="{{ route('executor.dashboard') }}" 
               class="block py-2 px-3 rounded-lg {{ request()->routeIs('executor.dashboard') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-slate-700 hover:bg-slate-50' }}">
                Мои задачи
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-slate-200 mt-auto">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left py-2 px-3 rounded-lg text-slate-700 hover:bg-slate-50">
                    Выйти
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        @if(session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <h3 class="font-semibold mb-2">Ошибки:</h3>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>


