@extends('account.layout')

@section('title', 'Редактировать объявление')
@section('header', 'Редактировать объявление')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 mb-1">Редактировать объявление</h1>
            <p class="text-sm text-slate-600">Обновите информацию об объявлении</p>
        </div>
        <a href="{{ route('account.classifieds.my-ads') }}" class="text-slate-600 hover:text-slate-900 transition-colors">
            ← Назад к списку
        </a>
    </div>

    {{-- Current Status --}}
    @if($ad->status === 'published')
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Объявление опубликовано</span>
            </div>
            <p class="text-sm mt-1">Изменения будут отправлены на повторную модерацию</p>
        </div>
    @elseif($ad->status === 'moderation')
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Объявление на модерации</span>
            </div>
        </div>
    @endif

    @if($ad->moderation_reason)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-medium mb-1">Причина отклонения:</p>
                    <p class="text-sm">{{ $ad->moderation_reason }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <div class="bg-white border border-slate-200 rounded-lg p-6">
        <form action="{{ route('account.classifieds.update', $ad) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Основная информация</h2>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Категория *</label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $ad->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Название *</label>
                    <input type="text" name="title" required value="{{ old('title', $ad->title) }}" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="Например: Продаю iPhone 13 Pro">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Описание *</label>
                    <textarea name="description" required rows="8" 
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              placeholder="Подробно опишите товар, его состояние, характеристики...">{{ old('description', $ad->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Цена (NOK)</label>
                        <input type="number" name="price_value" value="{{ old('price_value', $ad->price_value ? $ad->price_value / 100 : '') }}" 
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Оставьте пустым для договорной">
                        <p class="mt-1 text-xs text-slate-500">Оставьте пустым, если цена договорная</p>
                        @error('price_value')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Адрес</label>
                        <input type="text" name="address" value="{{ old('address', $ad->address) }}" 
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Город, улица, дом">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Status (only for draft) --}}
                @if($ad->status === 'draft')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Статус</label>
                        <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="draft" {{ old('status', $ad->status) === 'draft' ? 'selected' : '' }}>Черновик</option>
                            <option value="moderation" {{ old('status', $ad->status) === 'moderation' ? 'selected' : '' }}>Отправить на модерацию</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Statistics --}}
            <div class="border-t border-slate-200 pt-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-3">Статистика объявления</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-600 mb-1">Просмотры</div>
                        <div class="text-xl font-bold text-slate-900">{{ number_format($ad->views_count ?? 0, 0, ',', ' ') }}</div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-600 mb-1">Создано</div>
                        <div class="text-sm font-medium text-slate-900">{{ $ad->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                    @if($ad->published_at)
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-600 mb-1">Опубликовано</div>
                            <div class="text-sm font-medium text-slate-900">{{ $ad->published_at->format('d.m.Y H:i') }}</div>
                        </div>
                    @endif
                    @if($ad->expires_at)
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-600 mb-1">Истекает</div>
                            <div class="text-sm font-medium text-slate-900">{{ $ad->expires_at->format('d.m.Y') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-4 pt-4 border-t border-slate-200">
                <button type="submit" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                    Сохранить изменения
                </button>
                <a href="{{ route('account.classifieds.my-ads') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                    Отмена
                </a>
                <a href="{{ route('classifieds.show', $ad->slug) }}" target="_blank" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                    Просмотр
                </a>
            </div>
        </form>
    </div>

    {{-- Help Text --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <strong>💡 Совет:</strong> 
            @if($ad->status === 'published')
                После сохранения изменений объявление будет отправлено на повторную модерацию.
            @else
                После сохранения изменений объявление будет обновлено.
            @endif
        </p>
    </div>
</div>
@endsection

