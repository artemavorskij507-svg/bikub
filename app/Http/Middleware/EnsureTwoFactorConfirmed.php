<?php

namespace App\Http\Middleware;

use App\Models\UserTwoFactorSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnsureTwoFactorConfirmed
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Only enforce for admin/operator/dispatcher roles
        if (! in_array($user->role ?? null, ['admin', 'operator', 'dispatcher'], true)) {
            return $next($request);
        }

        // If already on the 2FA setup page, allow through
        if ($request->path() === 'admin/security/two-factor-setup' || str_contains($request->path(), 'two-factor-setup')) {
            return $next($request);
        }

        $setting = UserTwoFactorSetting::where('user_id', $user->id)->first();

        // Check if 2FA is confirmed (not just enabled)
        if (! $setting || ! $setting->confirmed_at) {
            // Redirect to 2FA setup page
            return Redirect::intended('/admin/security/two-factor-setup');
        }

        return $next($request);
    }
}
