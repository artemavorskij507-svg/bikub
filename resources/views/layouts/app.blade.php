<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="@yield('meta_description', 'BiKuBe marketplace and delivery services.')">
        @yield('meta_head')

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if (class_exists(\Livewire\Livewire::class))
            @livewireStyles
        @endif

        <!-- Mapbox GL JS -->
        <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
        <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />

        <!-- Alpine.js -->
        <script src="https://unpkg.com/alpinejs" defer></script>
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div id="app" class="min-h-screen flex flex-col">
            {{-- Navigation removed - Using custom navigation in individual pages --}}

            <main class="flex-grow">
                @yield('content')
                {{ $slot ?? '' }}
            </main>

            <footer class="bg-white border-t mt-12 py-8 text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} BiKuBe Ecosystem. All rights reserved.
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
