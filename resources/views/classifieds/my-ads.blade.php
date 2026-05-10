@extends('account.layout')

@section('title', 'Мои объявления')
@section('header', 'Мои объявления')

@section('content')
<div class="space-y-6">
    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <ul class="list-disc list-inside text-red-800 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 mb-1">Мои объявления</h1>
            <p class="text-sm text-slate-600">Управляйте своими объявлениями и отслеживайте статистику</p>
        </div>
        <a href="{{ route('account.classifieds.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Создать объявление
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">Всего</div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white border border-green-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">Опубликовано</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['published'] }}</div>
        </div>
        <div class="bg-white border border-yellow-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">На модерации</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['moderation'] }}</div>
        </div>
        <div class="bg-white border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">Продано</div>
            <div class="text-2xl font-bold text-blue-600">{{ $stats['sold'] }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">Черновики</div>
            <div class="text-2xl font-bold text-slate-600">{{ $stats['draft'] }}</div>
        </div>
        <div class="bg-white border border-purple-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-600 mb-1 uppercase tracking-wide">Просмотры</div>
            <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_views'], 0, ',', ' ') }}</div>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div class="bg-white border border-slate-200 rounded-lg p-4">
        <form method="GET" action="{{ route('account.classifieds.my-ads') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Поиск</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Название или описание..."
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Статус</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Все статусы</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="moderation" {{ request('status') === 'moderation' ? 'selected' : '' }}>На модерации</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>Продано</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Истекло</option>
                    </select>
                </div>

                {{-- Category Filter --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Категория</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Все категории</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                        Применить
                    </button>
                    <a href="{{ route('account.classifieds.my-ads') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                        Сбросить
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Ads List --}}
    @if($ads->isEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-slate-900 mb-2">Объявления не найдены</h3>
            <p class="text-slate-600 mb-6">
                @if(request()->hasAny(['search', 'status', 'category_id']))
                    Попробуйте изменить параметры поиска
                @else
                    Создайте первое объявление, чтобы начать продавать
                @endif
            </p>
            <a href="{{ route('account.classifieds.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Создать объявление
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($ads as $ad)
                <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-lg transition-all">
                    <div class="flex flex-col lg:flex-row gap-4">
                        {{-- Main Content --}}
                        <div class="flex-1">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-slate-900">
                                            <a href="{{ route('classifieds.show', $ad->slug) }}" target="_blank" class="hover:text-primary-600 transition-colors">
                                                {{ $ad->title }}
                                            </a>
                                        </h3>
                                        @if($ad->is_premium)
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">⭐ Premium</span>
                                        @endif
                                        @if($ad->highlight_expires_at && $ad->highlight_expires_at->isFuture())
                                            <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">✨ Выделено</span>
                                        @endif
                                        @if($ad->top_expires_at && $ad->top_expires_at->isFuture())
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">⬆️ Топ</span>
                                        @endif
                                    </div>
                                    
                                    {{-- Status Badge --}}
                                    <div class="mb-2">
                                        @if($ad->status === 'published')
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">Опубликовано</span>
                                        @elseif($ad->status === 'moderation')
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">На модерации</span>
                                        @elseif($ad->status === 'sold')
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Продано</span>
                                        @elseif($ad->status === 'draft')
                                            <span class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700 rounded-full">Черновик</span>
                                        @elseif($ad->status === 'expired')
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">Истекло</span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-slate-600 mb-2">
                                        <span class="font-medium">{{ $ad->category->name ?? 'Без категории' }}</span>
                                        @if($ad->shop)
                                            • <span class="text-primary-600">{{ $ad->shop->name }}</span>
                                        @endif
                                    </p>
                                    
                                    <p class="text-sm text-slate-700 line-clamp-2 mb-3">
                                        {{ \Illuminate\Support\Str::limit($ad->description, 150) }}
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-600">
                                        <span class="font-semibold text-slate-900 text-base">
                                            @if($ad->price_value)
                                                {{ number_format($ad->price_value / 100, 0, ',', ' ') }} NOK
                                            @else
                                                По договорённости
                                            @endif
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span>Создано: {{ $ad->created_at->format('d.m.Y H:i') }}</span>
                                        @if($ad->published_at)
                                            <span class="text-slate-300">•</span>
                                            <span>Опубликовано: {{ $ad->published_at->format('d.m.Y') }}</span>
                                        @endif
                                        @if($ad->views_count > 0)
                                            <span class="text-slate-300">•</span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                {{ number_format($ad->views_count, 0, ',', ' ') }} просмотров
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-2 lg:min-w-[200px]">
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('classifieds.show', $ad->slug) }}" target="_blank" 
                                   class="w-full px-4 py-2 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors text-center flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Просмотр
                                </a>
                                
                                @if($ad->status !== 'sold' && $ad->status !== 'expired')
                                    <a href="{{ route('account.classifieds.edit', $ad) }}" 
                                       class="w-full px-4 py-2 text-sm bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg transition-colors text-center flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Редактировать
                                    </a>
                                @endif

                                @if($ad->status === 'published')
                                    <form action="{{ route('account.classifieds.sold', $ad) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full px-4 py-2 text-sm bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Продано
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('account.classifieds.destroy', $ad) }}" method="POST" 
                                      onsubmit="return confirm('Вы уверены, что хотите удалить это объявление? Это действие нельзя отменить.');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full px-4 py-2 text-sm bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Удалить
                                    </button>
                                </form>
                            </div>

                            {{-- Moderation Reason --}}
                            @if($ad->moderation_reason)
                                <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                    <strong>Причина отклонения:</strong> {{ $ad->moderation_reason }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $ads->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
