<div class="container mx-auto pb-10">
    <div class="relative bg-gray-800 h-48 md:h-64 rounded-b-lg overflow-hidden mb-16">
        @if($shop->cover_path)
            <img src="{{ asset('storage/' . $shop->cover_path) }}" class="w-full h-full object-cover opacity-70">
        @endif
        <div class="absolute -bottom-12 left-6 md:left-10 flex items-end">
            <div class="w-24 h-24 md:w-32 md:h-32 bg-white rounded-lg shadow-lg overflow-hidden border-4 border-white">
                @if($shop->logo_path)
                    <img src="{{ asset('storage/' . $shop->logo_path) }}" class="w-full h-full object-contain">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="ml-4 mb-3 pb-1 md:pb-0">
                <h1 class="text-2xl md:text-3xl font-bold text-white shadow-black drop-shadow-md flex items-center gap-2">
                    {{ $shop->name }}
                    @if($shop->is_verified)
                        <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full" title="Verified Business">✓</span>
                    @endif
                </h1>
                @if($shop->address)
                    <p class="text-white text-sm opacity-90">{{ $shop->address }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-8 px-4 mt-8">
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-5 rounded shadow">
                <h3 class="font-bold text-lg border-b pb-2 mb-3">О магазине</h3>
                <p class="text-gray-600 text-sm mb-4">{{ $shop->description ?? 'Описание магазина ещё не заполнено.' }}</p>

                @if($shop->phone)
                    <div class="mb-2">
                        <span class="block text-xs text-gray-400 uppercase">Телефон</span>
                        <a href="tel:{{ $shop->phone }}" class="text-blue-600 font-medium">{{ $shop->phone }}</a>
                    </div>
                @endif

                @if($shop->website)
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">Сайт</span>
                        <a href="{{ $shop->website }}" target="_blank" rel="nofollow" class="text-blue-600 truncate block">
                            {{ $shop->website }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="md:col-span-3">
            <h2 class="text-xl font-bold mb-4">Товары ({{ $ads->total() }})</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($ads as $ad)
                    <div class="bg-white rounded-lg shadow hover:shadow-md transition duration-200 overflow-hidden flex flex-col h-full relative">
                        <div class="h-40 bg-gray-200 flex items-center justify-center text-gray-400">
                            Нет изображения
                        </div>
                        <div class="p-4 flex-grow">
                            <h3 class="font-bold text-lg truncate">
                                <a href="{{ route('classifieds.show', $ad->slug) }}">{{ $ad->title }}</a>
                            </h3>
                            <p class="text-blue-600 font-bold mt-1">{{ $ad->priceFormatted }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $ads->links() }}
            </div>
        </div>
    </div>
</div>


