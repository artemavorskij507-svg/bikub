<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GLF Bikube') }} - Virtual Office</title>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        .virtual-office-canvas {
            font-family: 'Nunito', sans-serif;
        }
        .agent {
            transition: transform 0.2s ease;
        }
        .agent:hover {
            transform: scale(1.1);
            z-index: 100;
        }
        .zone {
            transition: all 0.3s ease;
        }
        .zone:hover {
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        .zone-card, .agent-card, .task-card, .message-card {
            transition: all 0.2s ease;
        }
        .zone-card:hover, .agent-card:hover {
            transform: translateX(4px);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .status-pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="antialiased">
    <div id="app">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="flex items-center">
                            <span class="text-xl font-bold text-gray-900">GLF Bikube</span>
                        </a>
                        <div class="hidden sm:flex sm:items-center sm:ml-10 space-x-8">
                            <a href="{{ url('/') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Home</a>
                            <a href="{{ route('virtual-office.index') }}" class="text-blue-600 hover:text-blue-700 px-3 py-2 text-sm font-medium">Virtual Office</a>
                            <a href="{{ route('virtual-office.canvas') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Canvas</a>
                            <a href="{{ route('virtual-office.agents') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Agents</a>
                            <a href="{{ route('virtual-office.tasks') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Tasks</a>
                            <a href="{{ route('virtual-office.zones') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Zones</a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        @auth
                            <a href="{{ route('account.dashboard') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>
    </div>
    @livewireScripts
</body>
</html>
