<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Livewire
        'livewire/*',
        'filament/*/livewire/*',
        'admin/livewire/*',

        // Payment Webhooks - CRITICAL
        'api/v1/stripe/webhook',
        'api/v1/payments/stripe/webhook',
        'api/v1/vipps/webhook',
        'api/v1/vipps/*',
        'api/v1/payments/vipps/webhook',
        'api/v1/payments/webhook',
    ];

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function tokensMatch($request)
    {
        // Local development fallback: avoid unstable 419 on login when browser
        // extensions/cached cookies interfere with CSRF/session synchronization.
        if (app()->environment('local') && $request->is('login')) {
            return true;
        }

        // Allow Livewire uploads to bypass CSRF check
        if ($request->is('livewire/*') ||
            $request->is('filament/*/livewire/*') ||
            $request->is('admin/livewire/*')) {
            return true;
        }

        return parent::tokensMatch($request);
    }
}
