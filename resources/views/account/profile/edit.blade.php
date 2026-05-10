@extends('account.layout')

@section('title', 'Профиль — Личный кабинет')
@section('header', 'Профиль')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900">Основные данные профиля</h2>
        <p class="mt-1 text-sm text-slate-600">
            Эти данные используются в заказах, уведомлениях и для связи с вами.
        </p>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6" aria-label="Форма редактирования профиля">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">Имя и фамилия</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                        placeholder="Например: Иван Иванов"
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    <p class="mt-1 text-xs text-slate-500">Имя отображается в заказах и платежных документах.</p>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email для входа</label>
                    <input
                        type="email"
                        id="email"
                        value="{{ $user->email }}"
                        disabled
                        class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-slate-500"
                    >
                    <p class="mt-1 text-xs text-slate-500">Email изменяется в разделе «Безопасность».</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Телефон</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        autocomplete="tel"
                        placeholder="+47 123 45 678"
                        class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 @error('phone') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    <p class="mt-1 text-xs text-slate-500">Номер используется для связи по текущим заказам.</p>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @if($clientProfile && $clientProfile->address)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Адрес по умолчанию</h3>
                    <p class="mt-2 text-sm text-slate-700">
                        {{ $clientProfile->address->formatted_address ?? $clientProfile->address->street_address }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Этот адрес используется как точка по умолчанию при создании заказов.
                    </p>
                </div>
            @endif

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Сохраняются только имя и телефон. Остальные параметры меняются в соответствующих разделах кабинета.</p>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-primary-700">
                    Сохранить изменения
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
