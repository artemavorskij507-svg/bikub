<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Facades\Multitenancy;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Визначаємо тенанта за доменом
        $tenant = Multitenancy::findForRequest($request);

        if ($tenant) {
            Multitenancy::makeCurrent($tenant);
        }

        return $next($request);
    }
}
