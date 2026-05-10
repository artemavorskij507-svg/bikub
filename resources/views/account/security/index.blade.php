@extends('account.layout')

@section('title', 'Настройки безопасности')
@section('header', 'Безопасность аккаунта')

@section('content')
<div class="space-y-6">
    @if (session('status'))
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content">
                <p class="alert-text">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error" role="alert" aria-live="assertive">
            <div class="alert-content">
                <h2 class="alert-title">Проверьте данные формы</h2>
                <ul class="mt-2 list-disc space-y-1 pl-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Двухфакторная аутентификация</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $hasTwoFactor ? 'TOTP-защита активна. Вход подтверждается одноразовым кодом.' : 'Добавьте второй фактор, чтобы усилить защиту аккаунта.' }}
                </p>
            </div>
            @if ($hasTwoFactor)
                <x-account.status-badge label="Включена" tone="success" />
            @else
                <x-account.status-badge label="Выключена" tone="neutral" />
            @endif
        </div>

        @if (! $hasTwoFactor)
            <form method="POST" action="{{ route('account.security.2fa.enable') }}">
                @csrf
                <x-primary-button>Включить 2FA</x-primary-button>
            </form>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('account.security.2fa.disable') }}" data-confirm="Отключить двухфакторную аутентификацию? Это снизит уровень защиты аккаунта.">
                    @csrf
                    <x-secondary-button>Отключить 2FA</x-secondary-button>
                </form>
                <p class="text-xs text-slate-500">При повторном включении потребуется заново отсканировать QR-код и сохранить резервные коды.</p>
            </div>
        @endif
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Привязка eID</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $eidProvider ? 'Аккаунт уже связан с провайдером электронной идентификации.' : 'Подключите BankID, MinID или Buypass для быстрого и безопасного входа.' }}
            </p>
        </div>

        @if ($eidProvider)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p>Провайдер: <span class="font-semibold uppercase">{{ $eidProvider }}</span></p>
                @if ($eidNationalIdMasked)
                    <p class="mt-1">Национальный ID: {{ $eidNationalIdMasked }}</p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach (['bankid' => 'BankID', 'minid' => 'MinID', 'buypass' => 'Buypass ID'] as $provider => $label)
                    <form method="POST" action="{{ route('account.security.eid.link', $provider) }}">
                        @csrf
                        <x-primary-button class="w-full justify-center">{{ $label }}</x-primary-button>
                    </form>
                @endforeach
            </div>
        @endif
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Активные сессии</h2>
            <p class="mt-1 text-sm text-slate-600">Устройства, с которых выполнен вход в ваш аккаунт.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" aria-label="Список активных сессий">
                <caption class="sr-only">Активные сессии пользователя по устройствам</caption>
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-3 py-2">Устройство</th>
                        <th scope="col" class="px-3 py-2">IP</th>
                        <th scope="col" class="px-3 py-2">Последняя активность</th>
                        <th scope="col" class="px-3 py-2">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-3 py-3 text-slate-700">{{ \Illuminate\Support\Str::limit($session->user_agent, 70) ?: 'Неизвестное устройство' }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $session->ip_address ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ optional($session->last_activity)->diffForHumans() ?? '—' }}</td>
                            <td class="px-3 py-3">
                                @if ($session->session_id === $currentSessionId)
                                    <x-account.status-badge label="Текущая" tone="success" class="px-2 py-0.5" />
                                @else
                                    <x-account.status-badge label="Активна" tone="neutral" class="px-2 py-0.5" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-5 text-center text-slate-500">Активные сессии не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('account.security.sessions.logout-others') }}" class="max-w-md space-y-3" data-confirm="Завершить все другие сессии? На остальных устройствах потребуется повторный вход.">
            @csrf
            <div>
                <x-input-label for="logout_password" value="Подтвердите пароль" />
                <x-text-input id="logout_password" class="mt-1 block w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <x-secondary-button>Выйти со всех других устройств</x-secondary-button>
        </form>
    </section>
</div>
@endsection
