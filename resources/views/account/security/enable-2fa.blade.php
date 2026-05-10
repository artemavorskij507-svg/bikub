@extends('account.layout')

@section('title', 'Подключение 2FA')
@section('header', 'Настройка двухфакторной аутентификации')

@section('content')
<section class="mx-auto max-w-4xl bg-white border border-slate-200 rounded-xl p-5 sm:p-6 space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Шаг 1. Добавьте аккаунт в приложение-аутентификатор</h2>
        <p class="mt-1 text-sm text-slate-600">
            Сканируйте QR-код в Google Authenticator, Authy, 1Password или введите секрет вручную.
        </p>
    </header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[240px,1fr]">
        <div class="flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50 p-4">
            <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrUrl) }}"
                alt="QR-код для настройки 2FA"
                class="h-[220px] w-[220px] rounded-lg border border-slate-200 bg-white p-2"
            >
            <p class="mt-3 text-center text-xs text-slate-500">Сканируйте QR-код в приложении.</p>
        </div>

        <div class="space-y-5">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Секретный ключ</p>
                <p class="mt-2 break-all font-mono text-base text-slate-900">{{ $secret }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Резервные коды</p>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($recoveryCodes as $code)
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-800">{{ $code }}</div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Сохраните резервные коды в безопасном месте. Они понадобятся, если вы потеряете доступ к приложению 2FA.
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('account.security.2fa.confirm') }}" class="space-y-4 border-t border-slate-200 pt-5">
        @csrf
        <div>
            <x-input-label for="code" value="Шаг 2. Введите 6-значный код из приложения" />
            <x-text-input
                id="code"
                class="mt-1 block w-full sm:max-w-xs"
                type="text"
                name="code"
                required
                autofocus
                autocomplete="one-time-code"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-primary-button>Подтвердить и включить 2FA</x-primary-button>
            <a href="{{ route('account.security.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                Отмена
            </a>
        </div>
    </form>
</section>
@endsection
