<x-account-layout>
    <h1 class="text-2xl font-bold mb-6">My Favorites</h1>

    @if($favorites->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
            </svg>
            <p class="text-lg font-medium text-gray-900 mb-2">No favorites yet</p>
            <p class="text-sm text-gray-500 mb-6">Add items to your favorites to see them here</p>
            <a href="{{ route('classifieds.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Browse Ads
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($favorites as $favorite)
                @php $ad = $favorite->classifiedAd ?? null; @endphp
                @if($ad && $ad->status === 'published')
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-lg transition-all overflow-hidden">
                        <a href="{{ route('classifieds.show', $ad->slug) }}" class="block">
                            <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative overflow-hidden">
                                @if($ad->images && $ad->images->count() > 0)
                                    <img src="{{ $ad->main_image_url }}" 
                                         alt="{{ $ad->title }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                    {{ $ad->title }}
                                </h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ $ad->category->name ?? 'Без категории' }}
                                </p>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="font-bold text-lg text-blue-600">
                                        @if($ad->price_value)
                                            {{ number_format($ad->price_value / 100, 0, ',', ' ') }} NOK
                                        @else
                                            <span class="text-gray-500 text-sm">По договорённости</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ optional($ad->published_at ?? $ad->created_at)->format('d.m.Y') }}
                                    </div>
                                </div>
                            </div>
                        </a>
                        <div class="px-4 pb-4">
                            <form action="{{ route('account.classifieds.unfavorite', $ad) }}" method="POST" 
                                  onsubmit="return confirm('Удалить из избранного?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 text-sm bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors">
                                    Удалить из избранного
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(method_exists($favorites, 'links'))
            <div class="mt-6">
                {{ $favorites->links() }}
            </div>
        @endif
    @endif
</x-account-layout>
