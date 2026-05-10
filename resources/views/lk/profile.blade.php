@extends('lk.layout')

@section('title', 'Профиль')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Личный кабинет
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Профиль работника</h1>
            <p class="text-slate-500 font-medium mt-2">Управляйте своими личными данными и рабочим профилем</p>
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-2xl flex items-center gap-3 text-green-800 font-bold shadow-sm">
            <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center flex-shrink-0">✓</div>
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 shadow-sm">
            <div class="flex items-center gap-2 font-black mb-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ошибка в форме
            </div>
            <ul class="list-disc list-inside space-y-1 text-sm font-medium ml-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('lk.profile.update') }}" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Основная информация --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 h-full">
                <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    Основная информация
                </h2>
                
                <div class="space-y-6">
                    <div class="space-y-3">
                        <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Имя <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                            placeholder="Ваше имя">
                    </div>

                    <div class="space-y-3">
                        <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
                        <div class="relative">
                            <input type="email" id="email" value="{{ $user->email ?? '' }}" disabled readonly
                                class="w-full bg-slate-100 border-2 border-slate-200 rounded-2xl px-5 py-4 text-base font-bold text-slate-500 cursor-not-allowed">
                            <div class="absolute right-5 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-slate-400 pl-1">Email нельзя изменить</p>
                    </div>

                    <div class="space-y-3">
                        <label for="phone" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Телефон</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone ?? ($preferences['phone'] ?? '')) }}"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                            placeholder="+47 123 45 678">
                    </div>
                </div>
            </div>

            {{-- Рабочий профиль --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 h-full">
                <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    Рабочий профиль
                </h2>
                
                <div class="space-y-6">
                    <div class="space-y-3">
                        <label for="vehicle_type" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Тип ТС</label>
                        <input type="text" id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type', $employee->vehicle_type ?? ($preferences['vehicle_type'] ?? '')) }}"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                            placeholder="Например: авто, велосипед">
                        <p class="text-xs font-medium text-slate-400 pl-1">Тип вашего транспорта</p>
                    </div>

                    <div class="space-y-3">
                        <label for="vehicle_plate" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Номер ТС</label>
                        <input type="text" id="vehicle_plate" name="vehicle_plate" value="{{ old('vehicle_plate', $employee->vehicle_plate ?? ($preferences['vehicle_plate'] ?? '')) }}"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                            placeholder="ABC 123">
                    </div>

                    <div class="space-y-3">
                        <label for="zone" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Зона / Город</label>
                        <div class="relative">
                            <input type="text" id="zone" value="{{ $employee->currentZone->name ?? 'Не назначено' }}" disabled readonly
                                class="w-full bg-slate-100 border-2 border-slate-200 rounded-2xl px-5 py-4 text-base font-bold text-slate-500 cursor-not-allowed">
                            <div class="absolute right-5 top-1/2 -translate-y-1/2">
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-slate-400 pl-1">Зона назначается администратором</p>
                    </div>

                    <div class="space-y-3">
                        <label for="notes" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Примечание</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-medium text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300 resize-none"
                            placeholder="Дополнительная информация...">{{ old('notes', $employee->notes ?? ($preferences['worker_notes'] ?? '')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center gap-3 px-10 py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:bg-black hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                <span>Сохранить изменения</span>
                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </button>
        </div>
    </form>
</div>
@endsection