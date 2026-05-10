<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\RequestIdMiddleware::class,
        \App\Http\Middleware\EnsureAdminIpAllowed::class,
        \App\Http\Middleware\NormalizeLocalRedirects::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\AddSecurityHeaders::class,
        // \App\Http\Middleware\IdentifyTenant::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\InjectLkTheme::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetCurrentZone::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\ClearPendingAckFilter::class,
            // Response Cache middleware (commented out to avoid caching admin panel)
            // \Spatie\ResponseCache\Middleware\CacheResponse::class,
        ],

        'web.without-zone' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // SetCurrentZone виключений для адмінки
            // Но ClearPendingAckFilter нам нужен и для Filament-админки, чтобы чистить технические tableFilters в URL.
            \App\Http\Middleware\ClearPendingAckFilter::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\IdentifyTenantFromApiKey::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * Rate limiting profiles:
     * - api: 60 requests per minute (default)
     * - orders: 30 per minute (creating orders is resource-intensive)
     * - payments: 15 per minute (payment operations must be careful)
     * - price-calculation: 60 per minute (reading-heavy, not state-changing)
     * - price-estimate: 100 per minute (same as price-calculation)
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'zone.set' => \App\Http\Middleware\SetCurrentZone::class,
        'worker' => \App\Http\Middleware\EnsureWorker::class,
        '2fa.confirmed' => \App\Http\Middleware\EnsureTwoFactorConfirmed::class,
        'executor' => \App\Http\Middleware\EnsureUserIsExecutor::class,
    ];
}
