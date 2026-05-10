<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/account';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Default API rate limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Orders creation - lower limit for resource-intensive operations
        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Payment operations - strict limit for security
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        // Price calculations - higher limit for read-heavy operations
        RateLimiter::for('price-calculation', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Price estimates (legacy) - same as price-calculation
        RateLimiter::for('price-estimate', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        // Critical authenticated operations - 60 requests per minute
        RateLimiter::for('api_critical', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Guest authentication routes - strict limit to prevent abuse
        // Allow only 5 registration/password reset attempts per minute from same IP
        RateLimiter::for('guest_auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Agent OS / Team Chat API
        RateLimiter::for('agency-agents', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(90)->by('agency-agents:read:'.$identity),
                Limit::perMinute(30)->by('agency-agents:write:'.$identity),
            ];
        });

        // Route model binding для ErrandOrderDetails
        Route::bind('errand', function ($value) {
            return \App\Models\ErrandOrderDetails::findOrFail($value);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin routes без SetCurrentZone
            Route::middleware('web.without-zone')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
