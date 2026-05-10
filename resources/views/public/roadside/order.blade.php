@extends('layouts.app')

@section('title', 'Вызвать помощь на дороге')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-12">
    <h1 class="text-3xl font-bold text-slate-900 mb-8">Вызвать помощь на дороге</h1>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8">
            <div class="font-semibold text-red-900 mb-2">Проверьте форму:</div>
            <ul class="list-disc list-inside space-y-1 text-sm text-red-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.roadside.order.submit') }}" class="space-y-4 bg-white rounded-xl shadow-lg p-8">
        @csrf

        <label class="block">
            <span class="block text-sm font-medium text-slate-700 mb-2">Тип помощи *</span>
            <select name="preset_id" required class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                <option value="">Выберите тип помощи</option>
                @foreach($presets as $preset)
                    <option value="{{ $preset->id }}">{{ $preset->label }} 
                        @if($preset->base_price)
                            - {{ number_format($preset->base_price, 0, ',', ' ') }} kr
                        @endif
                    </option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block text-sm font-medium text-slate-700 mb-2">Адрес инцидента *</span>
            <input type="text" name="incident_address" value="{{ old('incident_address') }}" required 
                   class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                   placeholder="Укажите адрес или описание места">
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block">
                <span class="block text-sm font-medium text-slate-700 mb-2">Марка авто</span>
                <input type="text" name="vehicle_make" value="{{ old('vehicle_make', $userData['vehicle_make'] ?? '') }}" 
                       class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Например: Toyota">
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-slate-700 mb-2">Модель авто</span>
                <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $userData['vehicle_model'] ?? '') }}" 
                       class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Например: Corolla">
            </label>
        </div>

        <label class="block">
            <span class="block text-sm font-medium text-slate-700 mb-2">Номер авто</span>
            <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $userData['vehicle_plate'] ?? '') }}" 
                   class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                   placeholder="Например: AB12345">
        </label>

        <div class="pt-4">
            <button type="submit" class="w-full px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors font-semibold">
                Создать заказ
            </button>
        </div>
    </form>
</div>
@endsection
