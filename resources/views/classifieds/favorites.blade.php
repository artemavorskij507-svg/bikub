@extends('account.layout')

@section('title', 'Избранные объявления')
@section('header', 'Избранные объявления')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 mb-1">Избранные объявления</h1>
            <p class="text-sm text-slate-600">Ваши сохранённые объявления</p>
        </div>
        <a href="{{ route('classifieds.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Найти объявления
        </a>
    </div>

    {{-- Favorites List --}}
    @if($favorites->isEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Нет избранных объявлений</h3>
            <p class="text-slate-600 mb-6">Добавьте объявления в избранное, чтобы не потерять их</p>
            <a href="{{ route('classifieds.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                Перейти к объявлениям
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($favorites as $favorite)
                @php $ad = $favorite->classifiedAd ?? null; @endphp
                @if($ad && $ad->status === 'published')
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 hover:shadow-lg transition-all overflow-hidden">
                        <a href="{{ route('classifieds.show', $ad->slug) }}" class="block">
                            {{-- Image --}}
                            <div class="aspect-video bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center relative">
                                @if($ad->shop && $ad->shop->logo_path)
                                    <img src="{{ asset('storage/'.$ad->shop->logo_path) }}" 
                                         alt="{{ $ad->title }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <svg class="w-16 h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-slate-900 mb-2 line-clamp-2">
                                    {{ $ad->title }}
                                </h3>
                                <p class="text-sm text-slate-600 mb-2">
                                    {{ $ad->category->name ?? 'Без категории' }}
                                </p>
                                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                    <div class="font-bold text-lg text-primary-600">
                                        @if($ad->price_value)
                                            {{ number_format($ad->price_value / 100, 0, ',', ' ') }} NOK
                                        @else
                                            <span class="text-slate-500 text-sm">По договорённости</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ optional($ad->published_at ?? $ad->created_at)->format('d.m.Y') }}
                                    </div>
                                </div>
                            </div>
                        </a>
                        
                        {{-- Remove from favorites --}}
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

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $favorites->links() }}
        </div>
    @endif
</div>
@endsection

