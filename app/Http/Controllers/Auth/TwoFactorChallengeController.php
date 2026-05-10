<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorService;
use App\Services\Security\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected UserSessionService $sessionService
    ) {}

    public function create(): View
    {
        if (! session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('2fa:user:id');
        $remember = $request->session()->pull('2fa:remember', false);

        if (! $userId) {
            return redirect()->route('login')->withErrors(['code' => 'Сессия двухфакторной проверки истекла.']);
        }

        $user = \App\Models\User::find($userId);

        if (! $user || ! $user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['code' => 'Пользователь не найден или 2FA отключена.']);
        }

        $secret = decrypt($user->two_factor_secret);

        if (! $twoFactor->verifyCode($secret, $request->input('code'))) {
            if ($this->attemptRecoveryCode($user, $request->input('code'))) {
                return $this->finalize($request, $user, $remember);
            }

            return back()->withErrors(['code' => 'Неверный код 2FA.']);
        }

        return $this->finalize($request, $user, $remember);
    }

    protected function attemptRecoveryCode(\App\Models\User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = collect(json_decode(decrypt($user->two_factor_recovery_codes), true));

        $index = $codes->search($code);

        if ($index === false) {
            return false;
        }

        $codes->forget($index);
        $user->two_factor_recovery_codes = encrypt($codes->values()->all());
        $user->save();

        return true;
    }

    protected function finalize(Request $request, \App\Models\User $user, bool $remember): RedirectResponse
    {
        Auth::login($user, $remember);

        $request->session()->forget(['2fa:user:id']);
        $request->session()->put('two_factor_passed_at', now());
        $this->sessionService->register($user, $request);

        return redirect()->intended(\App\Providers\RouteServiceProvider::HOME);
    }
}
