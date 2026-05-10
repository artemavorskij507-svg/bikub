<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Пожалуйста, введите код из приложения-аутентификатора или один из резервных кодов.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.challenge') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Код 2FA или резервный код" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                Подтвердить вход
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

