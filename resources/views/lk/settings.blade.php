@extends('lk.layout')

@section('title', 'Настройки')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Конфигурация
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Настройки</h1>
            <p class="text-slate-500 font-medium mt-2">Управляйте уведомлениями и интерфейсом приложения</p>
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
                Ошибка
            </div>
            <ul class="list-disc list-inside space-y-1 text-sm font-medium ml-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('lk.settings.update') }}" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Notifications --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 h-full">
                <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    Уведомления
                </h2>

                <div class="space-y-4">
                    <label class="group relative flex items-start gap-4 p-5 rounded-3xl border-2 border-slate-100 cursor-pointer hover:border-amber-200 hover:bg-amber-50/30 transition-all">
                        <input type="hidden" name="notify_orders" value="0">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="notify_orders" value="1" {{ old('notify_orders', $settings['notify_orders'] ?? true) ? 'checked' : '' }} 
                                class="peer h-6 w-6 cursor-pointer appearance-none rounded-lg border-2 border-slate-300 bg-white checked:border-amber-500 checked:bg-amber-500 hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 transition-all">
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-lg group-hover:text-amber-700 transition-colors">Заказы</div>
                            <div class="text-sm font-medium text-slate-500 mt-1 leading-relaxed">Получайте уведомления о новых заказах, изменениях статуса и напоминания</div>
                        </div>
                    </label>

                    <label class="group relative flex items-start gap-4 p-5 rounded-3xl border-2 border-slate-100 cursor-pointer hover:border-amber-200 hover:bg-amber-50/30 transition-all">
                        <input type="hidden" name="notify_payouts" value="0">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="notify_payouts" value="1" {{ old('notify_payouts', $settings['notify_payouts'] ?? true) ? 'checked' : '' }} 
                                class="peer h-6 w-6 cursor-pointer appearance-none rounded-lg border-2 border-slate-300 bg-white checked:border-amber-500 checked:bg-amber-500 hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 transition-all">
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-lg group-hover:text-amber-700 transition-colors">Выплаты</div>
                            <div class="text-sm font-medium text-slate-500 mt-1 leading-relaxed">Узнавайте о начислениях, выплатах и статусе ваших запросов</div>
                        </div>
                    </label>

                    <label class="group relative flex items-start gap-4 p-5 rounded-3xl border-2 border-slate-100 cursor-pointer hover:border-amber-200 hover:bg-amber-50/30 transition-all">
                        <input type="hidden" name="notify_system" value="0">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="notify_system" value="1" {{ old('notify_system', $settings['notify_system'] ?? true) ? 'checked' : '' }} 
                                class="peer h-6 w-6 cursor-pointer appearance-none rounded-lg border-2 border-slate-300 bg-white checked:border-amber-500 checked:bg-amber-500 hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 transition-all">
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-lg group-hover:text-amber-700 transition-colors">Система</div>
                            <div class="text-sm font-medium text-slate-500 mt-1 leading-relaxed">Важные объявления, обновления и критичные сообщения</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="space-y-8">
                {{-- Interface --}}
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                    <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                        </div>
                        Интерфейс
                    </h2>

                    <div class="space-y-6">
                        <div class="space-y-3">
                            <label for="interface_lang" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Язык интерфейса</label>
                            <div class="relative">
                                <select id="interface_lang" name="interface_lang" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-0 transition-colors appearance-none cursor-pointer">
                                    <option value="ru" {{ old('interface_lang', $settings['interface_lang'] ?? 'ru') === 'ru' ? 'selected' : '' }}>🇷🇺 Русский</option>
                                    <option value="uk" {{ old('interface_lang', $settings['interface_lang'] ?? 'ru') === 'uk' ? 'selected' : '' }}>🇺🇦 Українська</option>
                                    <option value="no" {{ old('interface_lang', $settings['interface_lang'] ?? 'ru') === 'no' ? 'selected' : '' }}>🇳🇴 Norsk</option>
                                    <option value="en" {{ old('interface_lang', $settings['interface_lang'] ?? 'ru') === 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label for="interface_theme" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Тема оформления</label>
                            <div class="relative">
                                <select id="interface_theme" name="interface_theme" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-0 transition-colors appearance-none cursor-pointer">
                                    <option value="light" {{ old('interface_theme', $settings['interface_theme'] ?? 'light') === 'light' ? 'selected' : '' }}>☀️ Светлая тема</option>
                                    <option value="dark" {{ old('interface_theme', $settings['interface_theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>🌙 Тёмная тема</option>
                                    <option value="system" {{ old('interface_theme', $settings['interface_theme'] ?? 'light') === 'system' ? 'selected' : '' }}>⚙️ Как в системе</option>
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security --}}
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                    <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        Безопасность
                    </h2>

                    <div class="p-6 bg-red-50 rounded-3xl border border-red-100">
                        <p class="text-sm font-bold text-red-800 mb-4">Эта функция требует повторной авторизации:</p>
                        <a href="{{ route('lk.profile') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-red-600 rounded-2xl font-bold hover:bg-red-600 hover:text-white hover:shadow-lg transition-all text-sm border border-red-200 hover:border-red-600 w-full justify-center">
                            🔐 Изменить пароль
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-4 pt-4 border-t border-slate-100">
            <a href="{{ route('lk.dashboard') }}" class="px-8 py-4 text-slate-500 font-bold rounded-2xl hover:bg-slate-100 hover:text-slate-700 transition-all">
                Отмена
            </a>
            <button type="submit" class="inline-flex items-center gap-3 px-10 py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:bg-black hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                <span>Сохранить</span>
                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </button>
        </div>
    </form>
</div>
@endsection