@php
    $isFavorite = auth()->check() ? \App\Modules\Classifieds\Models\ClassifiedAdFavorite::where('user_id', auth()->id())
        ->where('classified_ad_id', $ad->id)
        ->exists() : false;
@endphp

<div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 hover:border-primary-400 hover:shadow-2xl transition-all overflow-hidden group relative transform hover:-translate-y-2 {{ isset($featured) && $featured ? 'ring-2 ring-yellow-400' : '' }}">
    {{-- Favorite Button (outside link) --}}
    @auth
        <div class="absolute top-3 right-3 z-20">
            <form action="{{ $isFavorite ? route('account.classifieds.unfavorite', $ad) : route('account.classifieds.favorite', $ad) }}" 
                  method="POST" 
                  class="inline"
                  onsubmit="event.stopPropagation(); return true;">
                @csrf
                @if($isFavorite)
                    @method('DELETE')
                @endif
                <button type="submit" 
                        onclick="event.stopPropagation();"
                        class="p-2.5 rounded-full {{ $isFavorite ? 'bg-red-500 text-white hover:bg-red-600 shadow-lg' : 'bg-white/95 backdrop-blur-sm text-gray-600 hover:bg-white hover:text-red-500' }} shadow-lg transition-all transform hover:scale-110">
                    <svg class="w-5 h-5" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        @if($isFavorite)
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        @endif
                    </svg>
                </button>
            </form>
        </div>
    @endauth

    <a href="{{ route('classifieds.show', $ad->slug) }}" class="block">
        {{-- Image --}}
        <div class="aspect-video bg-gradient-to-br from-slate-100 via-slate-200 to-slate-300 flex items-center justify-center relative overflow-hidden group/image">
            @php
                $imageUrl = $ad->main_image_url ?? null;
                $hasImage = $imageUrl && $imageUrl !== asset('images/placeholder.png') && !str_contains($imageUrl, 'placeholder');
            @endphp
            
            @if($hasImage)
                <img src="{{ $imageUrl }}" 
                     alt="{{ $ad->title }}"
                     class="w-full h-full object-cover group-hover/image:scale-110 transition-transform duration-700 ease-out"
                     loading="lazy"
                     onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                <div class="hidden w-full h-full flex flex-col items-center justify-center text-slate-400 bg-gradient-to-br from-slate-100 to-slate-200">
                    <svg class="w-16 h-16 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-xs font-medium text-slate-500 px-4 text-center">{{ \Illuminate\Support\Str::limit($ad->title, 30) }}</span>
                </div>
            @else
                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-gradient-to-br from-slate-100 to-slate-200">
                    <svg class="w-16 h-16 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-xs font-medium text-slate-500 px-4 text-center">{{ \Illuminate\Support\Str::limit($ad->title, 30) }}</span>
                </div>
            @endif
            
            {{-- Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            
            {{-- Image Count Badge --}}
            @if($ad->images && $ad->images->count() > 1)
                <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white px-2 py-1 rounded-lg text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                    📷 {{ $ad->images->count() }}
                </div>
            @endif
            
            {{-- Promotion Badges --}}
            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                @if($ad->is_premium)
                    <span class="px-3 py-1 text-xs font-bold bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900 rounded-full shadow-lg backdrop-blur-sm">⭐ Premium</span>
                @endif
                @if($ad->highlight_expires_at && $ad->highlight_expires_at->isFuture())
                    <span class="px-3 py-1 text-xs font-bold bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-full shadow-lg backdrop-blur-sm">✨ Выделено</span>
                @endif
                @if($ad->top_expires_at && $ad->top_expires_at->isFuture())
                    <span class="px-3 py-1 text-xs font-bold bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full shadow-lg backdrop-blur-sm">⬆️ Топ</span>
                @endif
                @if($ad->vip_expires_at && $ad->vip_expires_at->isFuture())
                    <span class="px-3 py-1 text-xs font-bold bg-gradient-to-r from-red-500 to-red-600 text-white rounded-full shadow-lg backdrop-blur-sm">👑 VIP</span>
                @endif
            </div>

            {{-- Shop Badge --}}
            @if($ad->shop)
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 text-xs font-bold bg-gradient-to-r from-green-500 to-green-600 text-white rounded-full shadow-lg backdrop-blur-sm">🏪 Магазин</span>
                </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="p-5">
            <div class="flex items-start justify-between gap-2 mb-3">
                <h3 class="text-lg font-black text-slate-900 group-hover:text-primary-600 transition-colors line-clamp-2 flex-1 leading-tight">
                    {{ $ad->title }}
                </h3>
            </div>

            <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 text-xs font-bold bg-primary-100 text-primary-700 rounded-full">
                    {{ $ad->category->name ?? 'Без категории' }}
                </span>
            </div>

            <p class="text-sm text-slate-600 mb-4 line-clamp-2 leading-relaxed">
                {{ \Illuminate\Support\Str::limit($ad->description, 100) }}
            </p>

            <div class="flex items-center justify-between pt-4 border-t-2 border-slate-100">
                <div class="font-black text-xl text-primary-600">
                    @if($ad->price_value)
                        {{ number_format($ad->price_value / 100, 0, ',', ' ') }} <span class="text-sm text-slate-500">NOK</span>
                    @else
                        <span class="text-slate-500 text-base font-semibold">По договорённости</span>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    @if($ad->views_count > 0)
                        <span class="flex items-center gap-1 font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            {{ number_format($ad->views_count, 0, ',', ' ') }}
                        </span>
                    @endif
                    <span class="font-semibold">{{ optional($ad->published_at ?? $ad->created_at)->format('d.m.Y') }}</span>
                </div>
            </div>
        </div>
    </a>
</div>


