<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name }} - GLF BiKube</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-gray-900">GLF BiKube</a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="/catalog" class="text-gray-500 hover:text-blue-600">Каталог</a>
                    <a href="/care" class="{{ $category->code === 'care' ? 'text-blue-600' : 'text-gray-500' }} hover:text-blue-600">Care</a>
                    <a href="/eco" class="{{ $category->code === 'eco' ? 'text-blue-600' : 'text-gray-500' }} hover:text-blue-600">Eco</a>
                    <a href="/market" class="{{ $category->code === 'market' ? 'text-blue-600' : 'text-gray-500' }} hover:text-blue-600">Market</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Category Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $category->name }}</h1>
            <p class="text-xl text-gray-600">{{ $category->description }}</p>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $service)
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $service->name }}</h3>
                    <p class="text-gray-600 mb-4">{{ $service->description }}</p>
                </div>
                
                @if($service->default_pricing)
                <div class="mb-4">
                    <span class="text-2xl font-bold text-blue-600">
                        {{ $service->default_pricing['base'] ?? '0' }} NOK
                    </span>
                    @if(isset($service->default_pricing['duration']))
                    <span class="text-gray-500 ml-2">
                        ({{ $service->default_pricing['duration'] }} мин)
                    </span>
                    @endif
                </div>
                @endif

                @if($service->skills && count($service->skills) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Требования:</h4>
                    <div class="flex flex-wrap gap-1">
                        @foreach($service->skills as $skill)
                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex justify-between items-center">
                    <a href="/order/{{ $service->code }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Заказать
                    </a>
                    <span class="text-sm text-gray-500">{{ $service->code }}</span>
                </div>
            </div>
            @endforeach
        </div>

        @if($services->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Услуги не найдены</h3>
            <p class="text-gray-600">В данной категории пока нет доступных услуг.</p>
        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; 2025 GLF BiKube. Все права защищены.</p>
            </div>
        </div>
    </footer>
</body>
</html>
