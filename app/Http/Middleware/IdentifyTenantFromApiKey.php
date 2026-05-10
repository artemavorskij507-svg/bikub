<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Facades\Multitenancy;

class IdentifyTenantFromApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Отримуємо API key з заголовку
        $apiKey = $request->header('X-API-Key');

        if ($apiKey) {
            // Шукаємо партнера за API ключем
            $settings = \App\Models\PartnerSettings::where('api_key', $apiKey)->first();

            if ($settings && $settings->partner) {
                Multitenancy::makeCurrent($settings->partner);
            }
        }

        return $next($request);
    }
}
