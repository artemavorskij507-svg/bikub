@extends('layouts.app')

@section('title', 'Эвакуатор и помощь на дороге')

@section('content')
<div class="max-w-2xl mx-auto py-10 px-6">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-4">Эвакуатор и помощь на дороге</h1>
        <p class="text-lg text-slate-700">
            Круглосуточная помощь на дороге в Нарвике и окрестностях. 
            Прикуривание, замена колеса, подвоз топлива, эвакуация.
        </p>
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
            <ul class="list-disc list-inside text-red-800">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.roadside.submit') }}" class="space-y-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        @csrf

        {{-- Type selection --}}
        <div>
            <label for="type" class="block text-sm font-medium text-slate-700 mb-2">
                Тип услуги <span class="text-red-500">*</span>
            </label>
            <select name="type" id="type" required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                <option value="">Выберите тип услуги</option>
                <option value="roadside" {{ old('type') === 'roadside' ? 'selected' : '' }}>
                    Помощь на дороге (прикурить, колесо, топливо…)
                </option>
                <option value="evacuator" {{ old('type') === 'evacuator' ? 'selected' : '' }}>
                    Эвакуатор / транспортировка
                </option>
                <option value="inspection" {{ old('type') === 'inspection' ? 'selected' : '' }}>
                    Осмотр авто перед покупкой
                </option>
            </select>
            @error('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Personal info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="full_name" class="block text-sm font-medium text-slate-700 mb-2">
                    Имя <span class="text-red-500">*</span>
                </label>
                <input type="text" name="full_name" id="full_name" required
                    value="{{ old('full_name') }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                    placeholder="Ваше имя">
                @error('full_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">
                    Телефон <span class="text-red-500">*</span>
                </label>
                <input type="tel" name="phone" id="phone" required
                    value="{{ old('phone') }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                    placeholder="+47 123 45 678">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Vehicle info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="vehicle_plate" class="block text-sm font-medium text-slate-700 mb-2">
                    Госномер
                </label>
                <input type="text" name="vehicle_plate" id="vehicle_plate"
                    value="{{ old('vehicle_plate') }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                    placeholder="AB12345">
                @error('vehicle_plate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vehicle_type" class="block text-sm font-medium text-slate-700 mb-2">
                    Тип авто
                </label>
                <select name="vehicle_type" id="vehicle_type"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value="">Выберите тип</option>
                    <option value="легковой" {{ old('vehicle_type') === 'легковой' ? 'selected' : '' }}>Легковой</option>
                    <option value="фургон" {{ old('vehicle_type') === 'фургон' ? 'selected' : '' }}>Фургон</option>
                    <option value="грузовой" {{ old('vehicle_type') === 'грузовой' ? 'selected' : '' }}>Грузовой</option>
                    <option value="мотоцикл" {{ old('vehicle_type') === 'мотоцикл' ? 'selected' : '' }}>Мотоцикл</option>
                </select>
                @error('vehicle_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Problem description --}}
        <div>
            <label for="problem" class="block text-sm font-medium text-slate-700 mb-2">
                Описание проблемы <span class="text-red-500">*</span>
            </label>
            <textarea name="problem" id="problem" required rows="4"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                placeholder="Опишите проблему подробно...">{{ old('problem') }}</textarea>
            @error('problem')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Location --}}
        <div>
            <label for="location_text" class="block text-sm font-medium text-slate-700 mb-2">
                Местонахождение <span class="text-red-500">*</span>
            </label>
            <textarea name="location_text" id="location_text" required rows="2"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                placeholder="Опишите ваше местоположение: возле Rema 1000, парковка, E6 в сторону...">{{ old('location_text') }}</textarea>
            <p class="mt-1 text-sm text-slate-500">Укажите адрес или ориентиры для быстрого поиска</p>
            @error('location_text')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Hidden coordinates (for future map integration) --}}
        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">

        {{-- Submit button --}}
        <div class="pt-4">
            <button type="submit"
                class="w-full px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors font-semibold text-lg">
                Вызвать помощь
            </button>
        </div>

        <p class="text-sm text-slate-500 text-center">
            Нажимая кнопку, вы соглашаетесь с обработкой персональных данных
        </p>
    </form>

    {{-- Contact info --}}
    <div class="mt-8 text-center text-slate-600">
        <p class="text-sm">
            Или звоните напрямую: <a href="tel:+4712345678" class="text-sky-600 hover:underline font-semibold">+47 12 34 56 78</a>
        </p>
    </div>
</div>
@endsection

