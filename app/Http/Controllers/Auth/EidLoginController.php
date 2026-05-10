<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OidcClientFactory;
use App\Services\Notifications\NotificationFeedService;
use App\Services\Security\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class EidLoginController extends Controller
{
    public function __construct(
        protected UserSessionService $sessionService,
        protected NotificationFeedService $feedService
    ) {}

    public function redirect(string $provider, OidcClientFactory $factory)
    {
        $config = config("eid.providers.$provider");

        if (! $config) {
            abort(404);
        }

        if (! Auth::check()) {
            Session::forget(['eid_link_mode', 'eid_link_user_id', 'eid_custom_redirect']);
        }

        $state = Str::random(40);

        session([
            'eid_login_provider' => $provider,
            'eid_login_state' => $state,
        ]);

        $customRedirect = session('eid_link_mode') ? session('eid_custom_redirect') : null;

        if ($customRedirect) {
            $config['redirect_uri'] = $customRedirect;
        }

        $client = $factory->make($config);

        return redirect()->away($client->authorizationUrl($state));
    }

    public function callback(Request $request, OidcClientFactory $factory)
    {
        $provider = session('eid_login_provider');
        $state = session('eid_login_state');

        if (! $provider || ! $state) {
            abort(400, 'Missing eID session.');
        }

        if (! hash_equals($state, $request->query('state', ''))) {
            abort(400, 'State mismatch.');
        }

        $code = $request->query('code');

        if (! $code) {
            abort(400, 'Missing authorization code.');
        }

        $config = config("eid.providers.$provider");

        if (! $config) {
            abort(400, 'Unknown eID provider.');
        }

        $client = $factory->make($config);
        $userInfo = $client->fetchUserInfo($code);

        $nationalId = $userInfo['national_id'] ?? $userInfo['pid'] ?? $userInfo['sub'] ?? null;
        $email = $userInfo['email'] ?? null;
        $name = trim(($userInfo['given_name'] ?? '').' '.($userInfo['family_name'] ?? ''));

        if (! $nationalId) {
            abort(400, 'Unable to determine identity from eID provider.');
        }

        $linkMode = session('eid_link_mode', false);
        $linkUserId = session('eid_link_user_id');

        if ($linkMode && $linkUserId) {
            $user = User::findOrFail($linkUserId);

            $existing = User::where('eid_national_id', $nationalId)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing) {
                abort(409, 'Этот eID уже привязан к другому аккаунту.');
            }

            $user->eid_national_id = $nationalId;
            $user->eid_provider = $provider;
            $user->save();

            $this->clearEidSession();
            $this->feedService->push(
                $user,
                'security.eid_linked',
                'security',
                'eID привязан',
                "Аккаунт связан с провайдером {$provider}."
            );

            return redirect()
                ->route('account.security.index')
                ->with('status', 'eID успешно привязан к вашему аккаунту.');
        }

        $user = User::query()
            ->where('eid_national_id', $nationalId)
            ->first();

        if (! $user && $email) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $name ?: 'GLF Bikube Client',
                'email' => $email ?: sprintf('eid_%s_%s@generated.local', $provider, Str::lower($nationalId)),
                'password' => bcrypt(Str::random(40)),
                'eid_national_id' => $nationalId,
                'eid_provider' => $provider,
            ]);
        } else {
            $user->forceFill([
                'eid_national_id' => $user->eid_national_id ?: $nationalId,
                'eid_provider' => $provider,
            ])->save();
        }

        $this->clearEidSession();

        Auth::guard('web')->login($user, true);
        session()->put('two_factor_passed_at', now());
        $this->sessionService->register($user, $request);

        $this->feedService->push(
            $user,
            'security.login_eid',
            'security',
            'Вход через eID',
            "Провайдер: {$provider}"
        );

        return redirect()->route('account.dashboard');
    }

    protected function clearEidSession(): void
    {
        Session::forget([
            'eid_login_provider',
            'eid_login_state',
            'eid_link_mode',
            'eid_link_user_id',
            'eid_link_provider',
            'eid_custom_redirect',
        ]);
    }
}
