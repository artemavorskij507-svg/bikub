@extends('layouts.app')

@section('title', 'SOS - Помощь на дороге')

@push('styles')
<style>
    .sos-banner {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.9; }
    }
    .incident-icon {
        transition: all 0.3s;
        cursor: pointer;
    }
    .incident-icon:hover {
        transform: scale(1.1);
    }
    .incident-icon.selected {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    .photo-preview {
        position: relative;
        display: inline-block;
        margin: 0.5rem;
    }
    .photo-preview img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 0.5rem;
    }
    .photo-preview .remove {
        position: absolute;
        top: -0.5rem;
        right: -0.5rem;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50">
    <!-- SOS Banner -->
    <div class="sos-banner text-white py-8 text-center">
        <div class="max-w-4xl mx-auto px-6">
            <h1 class="text-6xl font-bold mb-4">SOS</h1>
            <p class="text-2xl mb-2">Помощь на дороге</p>
            <p class="text-lg opacity-90">Мы поможем вам быстро и профессионально</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 py-8">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="sosForm" action="{{ route('public.roadside.sos.submit') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-8">
            @csrf

            <!-- Выбор типа проблемы -->
            <div class="mb-8">
                <label class="block text-lg font-semibold text-slate-900 mb-4">Выберите тип проблемы *</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="jump_start">
                        <div class="text-4xl mb-2">🔋</div>
                        <div class="text-sm font-medium">Прикуривание</div>
                        <input type="radio" name="incident_type" value="jump_start" class="hidden" required>
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="fuel">
                        <div class="text-4xl mb-2">⛽</div>
                        <div class="text-sm font-medium">Закончилось топливо</div>
                        <input type="radio" name="incident_type" value="fuel" class="hidden">
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="flat_tire">
                        <div class="text-4xl mb-2">🛞</div>
                        <div class="text-sm font-medium">Прокол колеса</div>
                        <input type="radio" name="incident_type" value="flat_tire" class="hidden">
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="locked_keys">
                        <div class="text-4xl mb-2">🔑</div>
                        <div class="text-sm font-medium">Закрыты ключи</div>
                        <input type="radio" name="incident_type" value="locked_keys" class="hidden">
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="engine_no_start">
                        <div class="text-4xl mb-2">🚗</div>
                        <div class="text-sm font-medium">Не заводится</div>
                        <input type="radio" name="incident_type" value="engine_no_start" class="hidden">
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="tow_needed">
                        <div class="text-4xl mb-2">🚛</div>
                        <div class="text-sm font-medium">Нужен эвакуатор</div>
                        <input type="radio" name="incident_type" value="tow_needed" class="hidden">
                    </div>
                    <div class="incident-icon border-2 border-slate-300 rounded-lg p-4 text-center" data-type="accident">
                        <div class="text-4xl mb-2">⚠️</div>
                        <div class="text-sm font-medium">ДТП</div>
                        <input type="radio" name="incident_type" value="accident" class="hidden">
                    </div>
                </div>
            </div>

            <!-- Геолокация -->
            <div class="mb-6">
                <label class="block text-lg font-semibold text-slate-900 mb-2">Местоположение *</label>
                <div class="bg-slate-50 rounded-lg p-4 mb-4">
                    <div id="map" class="w-full h-64 rounded-lg border border-slate-300 mb-4"></div>
                    <button type="button" id="getLocationBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        📍 Определить местоположение автоматически
                    </button>
                    <div class="mt-2 text-sm text-slate-600">
                        Координаты: <span id="coords">Не определены</span>
                    </div>
                </div>
                <input type="hidden" name="lat" id="lat" required>
                <input type="hidden" name="lng" id="lng" required>
            </div>

            <!-- Контактная информация -->
            <div class="mb-6">
                <label class="block text-lg font-semibold text-slate-900 mb-4">Контактная информация</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Имя</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Телефон *</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Автомобиль -->
            <div class="mb-6">
                <label class="block text-lg font-semibold text-slate-900 mb-4">Информация об автомобиле</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Марка</label>
                        <input type="text" name="vehicle_make" value="{{ old('vehicle_make') }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Модель</label>
                        <input type="text" name="vehicle_model" value="{{ old('vehicle_model') }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Описание -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Описание проблемы</label>
                <textarea name="description" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
            </div>

            <!-- Фото -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Фото (можно несколько)</label>
                <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center">
                    <input type="file" name="photos[]" id="photos" multiple accept="image/*" class="hidden">
                    <label for="photos" class="cursor-pointer">
                        <div class="text-4xl mb-2">📷</div>
                        <div class="text-slate-600">Нажмите для загрузки или перетащите файлы сюда</div>
                    </label>
                </div>
                <div id="photoPreview" class="mt-4"></div>
            </div>

            <!-- Submit -->
            <div class="text-center">
                <button type="submit" class="bg-red-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-red-700 transition-colors">
                    🆘 Отправить запрос на помощь
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Выбор типа проблемы
    document.querySelectorAll('.incident-icon').forEach(icon => {
        icon.addEventListener('click', function() {
            document.querySelectorAll('.incident-icon').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });

    // Геолокация
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const coordsSpan = document.getElementById('coords');
    const getLocationBtn = document.getElementById('getLocationBtn');

    function updateCoords(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
        coordsSpan.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    }

    getLocationBtn.addEventListener('click', function() {
        if (navigator.geolocation) {
            getLocationBtn.disabled = true;
            getLocationBtn.textContent = 'Определение...';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateCoords(lat, lng);
                    getLocationBtn.disabled = false;
                    getLocationBtn.textContent = '📍 Определить местоположение автоматически';
                },
                function(error) {
                    alert('Не удалось определить местоположение. Пожалуйста, укажите его вручную на карте.');
                    getLocationBtn.disabled = false;
                    getLocationBtn.textContent = '📍 Определить местоположение автоматически';
                }
            );
        } else {
            alert('Геолокация не поддерживается вашим браузером.');
        }
    });

    // Загрузка фото
    const photosInput = document.getElementById('photos');
    const photoPreview = document.getElementById('photoPreview');
    let photoFiles = [];

    photosInput.addEventListener('change', function(e) {
        photoFiles = Array.from(e.target.files);
        displayPhotos();
    });

    function displayPhotos() {
        photoPreview.innerHTML = '';
        photoFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'photo-preview';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="remove" onclick="removePhoto(${index})">×</div>
                `;
                photoPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function removePhoto(index) {
        photoFiles.splice(index, 1);
        const dt = new DataTransfer();
        photoFiles.forEach(file => dt.items.add(file));
        photosInput.files = dt.files;
        displayPhotos();
    }

    // Валидация формы
    document.getElementById('sosForm').addEventListener('submit', function(e) {
        if (!latInput.value || !lngInput.value) {
            e.preventDefault();
            alert('Пожалуйста, определите ваше местоположение.');
            return false;
        }

        const incidentType = document.querySelector('input[name="incident_type"]:checked');
        if (!incidentType) {
            e.preventDefault();
            alert('Пожалуйста, выберите тип проблемы.');
            return false;
        }
    });
</script>
@endpush
@endsection

