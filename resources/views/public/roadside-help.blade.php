@extends('layouts.app')

@section('title', 'Эвакуатор и помощь на дороге — GLF Bikube')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Hero Block --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">
                Эвакуатор и помощь на дороге
            </h1>
            <p class="text-xl text-slate-700 mb-2">
                Круглосуточная помощь в Нарвике и окрестностях
            </p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 max-w-2xl mx-auto mt-4">
                <p class="text-sm text-amber-800">
                    ⚠️ <strong>Экстренная ситуация?</strong> Звоните по телефону 
                    <a href="tel:+4712345678" class="font-semibold underline">+47 12 34 56 78</a>. 
                    Форму используйте для стандартных случаев.
                </p>
            </div>
        </div>

        {{-- Success message --}}
        @if(session('status'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800">{{ session('status') }}</p>
            </div>
        @endif

        {{-- Error messages --}}
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-red-900 mb-2">Ошибки валидации:</h3>
                <ul class="list-disc list-inside text-red-800">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('roadside.help.submit') }}" class="bg-white rounded-xl shadow-lg border border-slate-200 p-8 space-y-8">
            @csrf

            {{-- Contact Information --}}
            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Контактная информация</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                            Имя <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $userData['name'] ?? '') }}"
                               required
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">
                            Телефон <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               value="{{ old('phone', $userData['phone'] ?? '') }}"
                               required
                               placeholder="+47 12 34 56 78"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                            Email (опционально)
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email', $userData['email'] ?? '') }}"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>

            {{-- Service Type --}}
            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Тип услуги</h2>
                <div class="space-y-3">
                    <label class="flex items-start p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" 
                               name="service_type" 
                               value="roadside_assistance" 
                               {{ old('service_type') === 'roadside_assistance' ? 'checked' : '' }}
                               required
                               class="mt-1 mr-3">
                        <div>
                            <div class="font-medium text-slate-900">Помощь на дороге</div>
                            <div class="text-sm text-slate-600">Прикурить, замена колеса, подвоз топлива, открытие замка</div>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" 
                               name="service_type" 
                               value="vehicle_transport" 
                               {{ old('service_type') === 'vehicle_transport' ? 'checked' : '' }}
                               required
                               class="mt-1 mr-3">
                        <div>
                            <div class="font-medium text-slate-900">Эвакуатор / перевозка авто</div>
                            <div class="text-sm text-slate-600">Транспортировка автомобиля до СТО или другого места</div>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" 
                               name="service_type" 
                               value="vehicle_inspection" 
                               {{ old('service_type') === 'vehicle_inspection' ? 'checked' : '' }}
                               required
                               class="mt-1 mr-3">
                        <div>
                            <div class="font-medium text-slate-900">Осмотр авто перед покупкой</div>
                            <div class="text-sm text-slate-600">Предпокупочная проверка автомобиля</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Vehicle Information --}}
            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Информация об автомобиле</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="vehicle_make" class="block text-sm font-medium text-slate-700 mb-1">
                            Марка
                        </label>
                        <input type="text" 
                               name="vehicle_make" 
                               id="vehicle_make" 
                               value="{{ old('vehicle_make') }}"
                               placeholder="Toyota"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div>
                        <label for="vehicle_model" class="block text-sm font-medium text-slate-700 mb-1">
                            Модель
                        </label>
                        <input type="text" 
                               name="vehicle_model" 
                               id="vehicle_model" 
                               value="{{ old('vehicle_model') }}"
                               placeholder="Camry"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div>
                        <label for="vehicle_plate" class="block text-sm font-medium text-slate-700 mb-1">
                            Госномер
                        </label>
                        <input type="text" 
                               name="vehicle_plate" 
                               id="vehicle_plate" 
                               value="{{ old('vehicle_plate') }}"
                               placeholder="AB12345"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>

            {{-- Location --}}
            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Где вы находитесь</h2>
                <div class="space-y-4">
                    <div>
                        <label for="location_address" class="block text-sm font-medium text-slate-700 mb-1">
                            Адрес или описание места
                        </label>
                        <input type="text" 
                               name="location_address" 
                               id="location_address" 
                               value="{{ old('location_address') }}"
                               placeholder="Улица, дом, ориентиры"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="location_lat" class="block text-sm font-medium text-slate-700 mb-1">
                                Широта (опционально)
                            </label>
                            <input type="number" 
                                   name="location_lat" 
                                   id="location_lat" 
                                   value="{{ old('location_lat') }}"
                                   step="0.000001"
                                   placeholder="59.9139"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>

                        <div>
                            <label for="location_lng" class="block text-sm font-medium text-slate-700 mb-1">
                                Долгота (опционально)
                            </label>
                            <input type="number" 
                                   name="location_lng" 
                                   id="location_lng" 
                                   value="{{ old('location_lng') }}"
                                   step="0.000001"
                                   placeholder="10.7522"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                    </div>

                    <button type="button" 
                            id="get-location-btn"
                            class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm">
                        📍 Использовать моё местоположение
                    </button>
                </div>
            </div>

            {{-- Destination (only for towing) --}}
            <div id="destination-block" class="border-b border-slate-200 pb-6 hidden">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Куда везти</h2>
                <div>
                    <label for="destination_address" class="block text-sm font-medium text-slate-700 mb-1">
                        Адрес назначения (СТО, парковка, адрес)
                    </label>
                    <input type="text" 
                           name="destination_address" 
                           id="destination_address" 
                           value="{{ old('destination_address') }}"
                           placeholder="Укажите адрес, куда нужно доставить автомобиль"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            {{-- Problem Description --}}
            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Что случилось?</h2>
                <div class="space-y-4">
                    <div>
                        <label for="problem_type" class="block text-sm font-medium text-slate-700 mb-1">
                            Тип проблемы (опционально)
                        </label>
                        <select name="problem_type" 
                                id="problem_type"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="">Выберите тип проблемы</option>
                            <option value="Не заводится" {{ old('problem_type') === 'Не заводится' ? 'selected' : '' }}>Не заводится</option>
                            <option value="Севший АКБ" {{ old('problem_type') === 'Севший АКБ' ? 'selected' : '' }}>Севший АКБ</option>
                            <option value="Прокол колеса" {{ old('problem_type') === 'Прокол колеса' ? 'selected' : '' }}>Прокол колеса</option>
                            <option value="Нет топлива" {{ old('problem_type') === 'Нет топлива' ? 'selected' : '' }}>Нет топлива</option>
                            <option value="Закрыты ключи" {{ old('problem_type') === 'Закрыты ключи' ? 'selected' : '' }}>Закрыты ключи</option>
                            <option value="Авария" {{ old('problem_type') === 'Авария' ? 'selected' : '' }}>Авария</option>
                            <option value="Другое" {{ old('problem_type') === 'Другое' ? 'selected' : '' }}>Другое</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">
                            Дополнительная информация
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="4"
                                  placeholder="Опишите проблему подробнее..."
                                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end">
                <button type="submit" 
                        class="px-8 py-3 bg-sky-600 text-white rounded-lg font-semibold hover:bg-sky-700 transition-colors text-lg">
                    Отправить заявку
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Show/hide destination block based on service type
    document.querySelectorAll('input[name="service_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const destinationBlock = document.getElementById('destination-block');
            if (this.value === 'vehicle_transport') {
                destinationBlock.classList.remove('hidden');
            } else {
                destinationBlock.classList.add('hidden');
            }
        });
    });

    // Get user location
    document.getElementById('get-location-btn')?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            alert('Геолокация не поддерживается вашим браузером');
            return;
        }

        this.disabled = true;
        this.textContent = 'Определение местоположения...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('location_lat').value = position.coords.latitude.toFixed(6);
                document.getElementById('location_lng').value = position.coords.longitude.toFixed(6);
                document.getElementById('get-location-btn').disabled = false;
                document.getElementById('get-location-btn').textContent = '📍 Использовать моё местоположение';
            },
            function(error) {
                alert('Не удалось определить местоположение: ' + error.message);
                document.getElementById('get-location-btn').disabled = false;
                document.getElementById('get-location-btn').textContent = '📍 Использовать моё местоположение';
            }
        );
    });

    // Check initial service type
    document.querySelector('input[name="service_type"]:checked')?.dispatchEvent(new Event('change'));
</script>
@endpush
@endsection

