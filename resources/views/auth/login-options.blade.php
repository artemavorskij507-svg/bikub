<x-guest-layout>
    <div class="w-full max-w-md space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-semibold text-slate-900">Вход в GLF Bikube</h1>
            <p class="text-sm text-slate-600">Выберите удобный способ авторизации</p>
        </div>

        <div class="bg-white shadow rounded-xl p-5 space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email + пароль</h2>
            <a href="{{ route('login') }}"
               class="block w-full border border-slate-200 rounded-lg py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">
                Войти по email
            </a>
            <a href="{{ route('register') }}"
               class="block w-full border border-slate-200 rounded-lg py-2 text-center text-sm text-slate-600 hover:bg-slate-50">
                Зарегистрироваться
            </a>
        </div>

        <div class="bg-white shadow rounded-xl p-5 space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Через eID</h2>

            <a href="{{ route('auth.eid.redirect', 'bankid') }}"
               class="block w-full rounded-lg py-2 text-center text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                BankID
            </a>

            <a href="{{ route('auth.eid.redirect', 'minid') }}"
               class="block w-full rounded-lg py-2 text-center text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                MinID
            </a>

            <a href="{{ route('auth.eid.redirect', 'buypass') }}"
               class="block w-full rounded-lg py-2 text-center text-sm font-medium text-white bg-slate-800 hover:bg-slate-900">
                Buypass ID
            </a>
        </div>
    </div>
</x-guest-layout>

