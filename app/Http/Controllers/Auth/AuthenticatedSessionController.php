<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\Notifications\NotificationFeedService;
use App\Services\Security\UserSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected UserSessionService $sessionService,
        protected NotificationFeedService $feedService
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->forget('two_factor_passed_at');

        $user = $request->user();

        if ($user?->hasTwoFactorEnabled()) {
            $request->session()->put('2fa:user:id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));
            Auth::logout();

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();
        $request->session()->put('two_factor_passed_at', now());

        if ($user) {
            $this->sessionService->register($user, $request);
        }

        $this->feedService->push(
            $user,
            'security.login',
            'security',
            'New account login',
            'Signed in with email and password'
        );

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $this->sessionService->deleteCurrent($request);

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        $request->session()->forget('two_factor_passed_at');

        return redirect('/');
    }
}

