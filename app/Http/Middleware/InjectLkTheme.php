<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectLkTheme
{
    /**
     * If route matches /lk* then share view variables to enable LK theme.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('lk') || $request->is('lk/*') || $request->routeIs('lk.*')) {
            // Share with views so layout can add body class and include assets
            view()->share('use_lk_theme', true);
            view()->share('body_class', trim((view()->shared('body_class') ?? '').' lk-theme'));
        }

        return $next($request);
    }
}
