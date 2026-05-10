@extends('account.layout')

@section('title', 'Создать объявление')
@section('header', 'Создать объявление')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 mb-1">Создать объявление</h1>
            <p class="text-sm text-slate-600">Заполните форму для публикации объявления</p>
        </div>
        <a href="{{ route('account.classifieds.my-ads') }}" class="text-slate-600 hover:text-slate-900 transition-colors">
            ← Назад к списку
        </a>
    </div>

    {{-- Form --}}
    <div class="bg-white border border-slate-200 rounded-lg p-6">
        <form action="{{ route('account.classifieds.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Basic Info --}}
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Основная информация</h2>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Категория *</label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Название *</label>
                    <input type="text" name="title" required value="{{ old('title') }}" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="Например: Продаю iPhone 13 Pro">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Описание *</label>
                    <textarea name="description" required rows="6" 
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              placeholder="Подробно опишите товар, его состояние, характеристики...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Цена (NOK)</label>
                        <input type="number" name="price_value" value="{{ old('price_value') }}" 
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Оставьте пустым для договорной">
                        <p class="mt-1 text-xs text-slate-500">Оставьте пустым, если цена договорная</p>
                        @error('price_value')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Адрес</label>
                        <input type="text" name="address" value="{{ old('address') }}" 
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                               placeholder="Город, улица, дом">
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Photo Upload --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Фотографии (до 10 шт.)</label>
                    <div class="border-2 border-dashed border-slate-300 rounded-lg p-6">
                        <input type="file" name="images[]" multiple accept="image/*" 
                               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                               id="image-upload">
                        <p class="mt-2 text-xs text-slate-500">Максимум 10 фотографий, до 5MB каждая. Форматы: JPG, PNG, WEBP</p>
                        <div id="image-preview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                    </div>
                    @error('images.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-4 pt-4 border-t border-slate-200">
                <button type="submit" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                    Опубликовать
                </button>
                <a href="{{ route('account.classifieds.my-ads') }}" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                    Отмена
                </a>
            </div>
        </form>
    </div>

    {{-- Help Text --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <strong>💡 Совет:</strong> После создания объявление будет отправлено на модерацию. После проверки оно будет опубликовано на сайте.
        </p>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('image-upload')?.addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (e.target.files.length > 10) {
        alert('Можно загрузить максимум 10 фотографий');
        e.target.value = '';
        return;
    }
    
    Array.from(e.target.files).forEach((file, index) => {
        if (file.size > 5 * 1024 * 1024) {
            alert(`Файл "${file.name}" слишком большой (максимум 5MB)`);
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.innerHTML = `
                <img src="${event.target.result}" class="w-full h-32 object-cover rounded-lg border border-slate-200">
                <button type="button" onclick="this.parentElement.remove(); updateFileInput();" 
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition">
                    ×
                </button>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

function updateFileInput() {
    const preview = document.getElementById('image-preview');
    const input = document.getElementById('image-upload');
    const dt = new DataTransfer();
    
    // Сохраняем только те файлы, которые остались в preview
    // Это упрощенная версия - в реальности нужно отслеживать индексы
    if (preview.children.length === 0) {
        input.value = '';
    }
}
</script>
@endpush
@endsection
