<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationFeedService;
use App\Services\Security\TwoFactorService;
use App\Services\Security\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactor,
        protected UserSessionService $sessionService,
        protected NotificationFeedService $feedService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $sessions = $user->sessions()->orderByDesc('last_activity')->get();
        $currentSessionId = $request->session()->getId();

        return view('account.security.index', [
            'user' => $user,
            'hasTwoFactor' => $user->hasTwoFactorEnabled(),
            'eidProvider' => $user->eid_provider,
            'eidNationalIdMasked' => $this->maskNationalId($user->eid_national_id),
            'sessions' => $sessions,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    public function enableTwoFactor(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('account.security.index')->with('status', '2FA уже включена.');
        }

        $secret = $this->twoFactor->generateSecret();
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        session([
            '2fa_secret_pending' => $secret,
            '2fa_recovery_codes_pending' => $recoveryCodes,
        ]);

        $qrUrl = $this->twoFactor->makeQrUri($user, $secret);

        return view('account.security.enable-2fa', [
            'qrUrl' => $qrUrl,
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = session('2fa_secret_pending');
        $recoveryCodes = session('2fa_recovery_codes_pending');

        if (! $secret || ! $recoveryCodes) {
            return redirect()
                ->route('account.security.index')
                ->withErrors(['code' => 'Сессия настройки 2FA истекла. Попробуйте начать заново.']);
        }

        if (! $this->twoFactor->verifyCode($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Неверный код, попробуйте еще раз.']);
        }

        $user = $request->user();
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->forget(['2fa_secret_pending', '2fa_recovery_codes_pending']);
        $request->session()->put('two_factor_passed_at', now());
        $this->feedService->push(
            $user,
            'security.two_factor_enabled',
            'security',
            '2FA включена',
            'Двухфакторная аутентификация успешно включена.'
        );

        return redirect()
            ->route('account.security.index')
            ->with('status', 'Двухфакторная аутентификация успешно включена.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $request->session()->forget('two_factor_passed_at');
        $this->feedService->push(
            $user,
            'security.two_factor_disabled',
            'security',
            '2FA выключена',
            'Вы отключили двухфакторную аутентификацию.'
        );

        return redirect()
            ->route('account.security.index')
            ->with('status', 'Двухфакторная аутентификация отключена.');
    }

    public function startEidLink(string $provider): RedirectResponse
    {
        $config = config("eid.providers.$provider");

        if (! $config) {
            abort(404);
        }

        session()->forget([
            'eid_link_mode',
            'eid_link_provider',
            'eid_link_user_id',
            'eid_custom_redirect',
        ]);

        session([
            'eid_link_mode' => true,
            'eid_link_provider' => $provider,
            'eid_link_user_id' => auth()->id(),
            'eid_custom_redirect' => route('account.security.eid.callback', $provider),
        ]);

        return redirect()->route('auth.eid.redirect', $provider);
    }

    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->input('password'));

        $this->sessionService->deleteOtherSessions(
            $request->user(),
            $request->session()->getId()
        );

        $this->feedService->push(
            $request->user(),
            'security.sessions_terminated',
            'security',
            'Завершены другие сессии',
            'Вы завершили все другие активные сессии.'
        );

        return back()->with('status', 'Все остальные сессии завершены.');
    }

    protected function maskNationalId(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (strlen($value) <= 4) {
            return '****';
        }

        return substr($value, 0, 2).str_repeat('*', max(strlen($value) - 4, 2)).substr($value, -2);
    }
}
