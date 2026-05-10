@extends('layouts.app')

@section('title', $service->name . ' — Мастер на час — GLF Bikube')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-amber-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Back link --}}
        <a href="{{ route('handyman.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">
            ← Назад к каталогу
        </a>

        {{-- Service Header --}}
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-8 mb-6">
            <h1 class="text-3xl font-bold text-slate-900 mb-4">{{ $service->name }}</h1>
            
            @if($service->description)
                <p class="text-slate-700 mb-6">{{ $service->description }}</p>
            @endif

            <div class="flex items-center gap-6">
                <div>
                    @if($service->pricing_mode === 'FIXED')
                        <div class="text-3xl font-bold text-slate-900">
                            {{ number_format($service->base_rate_minor / 100, 0, ',', ' ') }} NOK
                        </div>
                        <div class="text-sm text-slate-500">фиксированная цена</div>
                    @else
                        <div class="text-3xl font-bold text-slate-900">
                            {{ number_format($service->base_rate_minor / 100, 0, ',', ' ') }} NOK/час
                        </div>
                        <div class="text-sm text-slate-500">почасовая оплата</div>
                    @endif
                </div>
                @if($service->estimated_duration_minutes)
                    <div class="text-sm text-slate-600">
                        Примерное время: {{ ceil($service->estimated_duration_minutes / 60) }} ч
                    </div>
                @endif
            </div>
        </div>

        {{-- Booking Form --}}
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-8">
            <h2 class="text-2xl font-semibold text-slate-900 mb-6">Оформить заказ</h2>

            @if(session('status'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <p class="text-green-800">{{ session('status') }}</p>
                </div>
            @endif

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

            <form method="POST" action="{{ route('handyman.service.book', $service->slug) }}" class="space-y-6">
                @csrf

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                        Опишите задачу
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4" 
                              class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                              placeholder="Что нужно сделать?">{{ old('description') }}</textarea>
                </div>

                {{-- Context Notes --}}
                <div>
                    <label for="context_notes" class="block text-sm font-medium text-slate-700 mb-2">
                        Дополнительная информация (помещение, условия и т.п.)
                    </label>
                    <textarea id="context_notes" 
                              name="context_notes" 
                              rows="3" 
                              class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                              placeholder="Этаж, наличие лифта, особые условия...">{{ old('context_notes') }}</textarea>
                </div>

                {{-- Expected Duration --}}
                @if($service->pricing_mode === 'HOURLY')
                    <div>
                        <label for="expected_duration_minutes" class="block text-sm font-medium text-slate-700 mb-2">
                            Ожидаемое время работы (минуты)
                        </label>
                        <input type="number" 
                               id="expected_duration_minutes" 
                               name="expected_duration_minutes" 
                               min="30" 
                               max="480" 
                               step="30"
                               value="{{ old('expected_duration_minutes', $service->estimated_duration_minutes) }}"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <p class="text-xs text-slate-500 mt-1">Минимум 30 минут, максимум 8 часов</p>
                    </div>
                @endif

                {{-- Materials Purchase --}}
                <div class="flex items-start">
                    <input type="checkbox" 
                           id="needs_materials_purchase" 
                           name="needs_materials_purchase" 
                           value="1"
                           {{ old('needs_materials_purchase') ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 text-amber-600 focus:ring-amber-500 border-slate-300 rounded">
                    <label for="needs_materials_purchase" class="ml-2 text-sm text-slate-700">
                        Нужно купить материалы (мастер закупит и предоставит чек)
                    </label>
                </div>

                {{-- Materials Notes --}}
                <div id="materials_notes_wrapper" style="display: none;">
                    <label for="materials_notes" class="block text-sm font-medium text-slate-700 mb-2">
                        Что нужно купить? (предпочтения по материалам)
                    </label>
                    <textarea id="materials_notes" 
                              name="materials_notes" 
                              rows="2" 
                              class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                              placeholder="Например: краска белая, кисти, валики...">{{ old('materials_notes') }}</textarea>
                </div>

                {{-- Address --}}
                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Адрес выполнения работ</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="address_line" class="block text-sm font-medium text-slate-700 mb-2">
                                Адрес <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="address_line" 
                                   name="address_line" 
                                   required
                                   value="{{ old('address_line') }}"
                                   class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-slate-700 mb-2">
                                    Почтовый индекс <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="postal_code" 
                                       name="postal_code" 
                                       required
                                       value="{{ old('postal_code') }}"
                                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>

                            <div>
                                <label for="city" class="block text-sm font-medium text-slate-700 mb-2">
                                    Город <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="city" 
                                       name="city" 
                                       required
                                       value="{{ old('city', 'Narvik') }}"
                                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                    <div>
                        <div class="text-sm text-slate-600">Примерная стоимость:</div>
                        <div class="text-2xl font-bold text-slate-900" id="estimated_price">
                            @if($service->pricing_mode === 'FIXED')
                                {{ number_format($service->base_rate_minor / 100, 0, ',', ' ') }} NOK
                            @else
                                Рассчитывается по времени
                            @endif
                        </div>
                    </div>
                    <button type="submit" 
                            class="px-8 py-3 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition font-semibold">
                        Оформить заказ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show/hide materials notes
    document.getElementById('needs_materials_purchase').addEventListener('change', function() {
        document.getElementById('materials_notes_wrapper').style.display = this.checked ? 'block' : 'none';
    });

    // Calculate estimated price for hourly services
    @if($service->pricing_mode === 'HOURLY')
    const baseRate = {{ $service->base_rate_minor }};
    const durationInput = document.getElementById('expected_duration_minutes');
    const priceDisplay = document.getElementById('estimated_price');
    
    if (durationInput) {
        durationInput.addEventListener('input', function() {
            const minutes = parseInt(this.value) || 60;
            const hours = Math.max(minutes / 60, 1);
            const price = Math.round(baseRate * hours);
            priceDisplay.textContent = (price / 100).toLocaleString('ru-RU') + ' NOK';
        });
    }
    @endif
</script>
@endsection

