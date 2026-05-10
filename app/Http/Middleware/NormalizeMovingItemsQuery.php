<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeMovingItemsQuery
{
    /**
     * If request is for Filament moving items and contains tableFilters query params,
     * redirect to the same path without query string.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only act on the specific admin moving items path
        if ($request->is('admin/moving/moving-items')) {
            // If there are any tableFilters in query string, remove them
            if ($request->query->has('tableFilters')) {
                // Build clean URL (path only, no query)
                $cleanUrl = $request->getSchemeAndHttpHost().$request->getPathInfo();

                return redirect()->to($cleanUrl, 302);
            }
        }

        return $next($request);
    }
}
