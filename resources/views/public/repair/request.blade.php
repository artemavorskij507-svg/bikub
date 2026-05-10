@extends('layouts.app')

@section('title', 'Заявка на комплексный ремонт — GLF Bikube')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8">
            <a href="{{ route('repair.index') }}" class="text-blue-600 hover:underline mb-4 block">&larr; Назад к услуге</a>

            <h1 class="text-3xl font-bold text-gray-900 mb-6">Заявка на комплексный ремонт</h1>

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

            <form method="POST" action="{{ route('repair.request.store') }}" class="space-y-8">
                @csrf

                {{-- Блок «Об объекте» --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Об объекте</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="object_type" class="block text-sm font-medium text-slate-700 mb-2">
                                Тип объекта <span class="text-red-500">*</span>
                            </label>
                            <select name="object_type" id="object_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                <option value="">Выберите тип объекта</option>
                                <option value="apartment" {{ old('object_type') === 'apartment' ? 'selected' : '' }}>Квартира</option>
                                <option value="house" {{ old('object_type') === 'house' ? 'selected' : '' }}>Дом</option>
                                <option value="office" {{ old('object_type') === 'office' ? 'selected' : '' }}>Офис</option>
                                <option value="other" {{ old('object_type') === 'other' ? 'selected' : '' }}>Другое</option>
                            </select>
                            @error('object_type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="area_sqm" class="block text-sm font-medium text-slate-700 mb-2">
                                Площадь (м²)
                            </label>
                            <input type="number" name="area_sqm" id="area_sqm" min="1" max="10000" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('area_sqm') }}">
                            @error('area_sqm')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="repair_type" class="block text-sm font-medium text-slate-700 mb-2">
                                Тип ремонта <span class="text-red-500">*</span>
                            </label>
                            <select name="repair_type" id="repair_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                <option value="">Выберите тип ремонта</option>
                                <option value="cosmetic" {{ old('repair_type') === 'cosmetic' ? 'selected' : '' }}>Косметический</option>
                                <option value="capital" {{ old('repair_type') === 'capital' ? 'selected' : '' }}>Капитальный</option>
                                <option value="office" {{ old('repair_type') === 'office' ? 'selected' : '' }}>Офисный</option>
                            </select>
                            @error('repair_type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Блок «Сроки и бюджет» --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Сроки и бюджет</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="desired_start_at" class="block text-sm font-medium text-slate-700 mb-2">
                                Желаемое начало работ
                            </label>
                            <input type="date" name="desired_start_at" id="desired_start_at" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('desired_start_at') }}">
                            @error('desired_start_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="desired_finish_at" class="block text-sm font-medium text-slate-700 mb-2">
                                Желаемое окончание работ
                            </label>
                            <input type="date" name="desired_finish_at" id="desired_finish_at" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('desired_finish_at') }}">
                            @error('desired_finish_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="budget_expectation" class="block text-sm font-medium text-slate-700 mb-2">
                            Ожидаемый бюджет
                        </label>
                        <input type="text" name="budget_expectation" id="budget_expectation" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Например: до 500 000 NOK" value="{{ old('budget_expectation') }}">
                        @error('budget_expectation')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- Блок «Описание и материалы» --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Описание и материалы</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="project_title" class="block text-sm font-medium text-slate-700 mb-2">
                                Название проекта (необязательно)
                            </label>
                            <input type="text" name="project_title" id="project_title" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('project_title') }}">
                            @error('project_title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                                Описание объекта и желаемого ремонта <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="6" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>{{ old('description') }}</textarea>
                            @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="design_project_url" class="block text-sm font-medium text-slate-700 mb-2">
                                Ссылка на дизайн-проект (если есть)
                            </label>
                            <input type="url" name="design_project_url" id="design_project_url" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('design_project_url') }}">
                            @error('design_project_url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">
                                Дополнительные заметки
                            </label>
                            <textarea name="notes" id="notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('notes') }}</textarea>
                            @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Блок «Адрес объекта» --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Адрес объекта</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="address_line" class="block text-sm font-medium text-slate-700 mb-2">
                                Адрес <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="address_line" id="address_line" autocomplete="street-address" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('address_line', auth()->user()->address?->address_line) }}" required>
                            @error('address_line')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-slate-700 mb-2">
                                    Почтовый индекс <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="postal_code" id="postal_code" autocomplete="postal-code" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('postal_code', auth()->user()->address?->postal_code) }}" required>
                                @error('postal_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="city" class="block text-sm font-medium text-slate-700 mb-2">
                                    Город <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="city" id="city" autocomplete="address-level2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('city', auth()->user()->address?->city) }}" required>
                                @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="mt-6">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Отправить заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

