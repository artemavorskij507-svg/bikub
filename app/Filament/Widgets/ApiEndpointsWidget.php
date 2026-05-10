<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Route;

class ApiEndpointsWidget extends Widget
{
    protected static string $view = 'filament.widgets.api-endpoints';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $routes = collect(Route::getRoutes())
            ->map(function ($route) {
                $methods = method_exists($route, 'methods') ? $route->methods() : [];
                $uri = method_exists($route, 'uri') ? $route->uri() : '';
                $name = method_exists($route, 'getName') ? $route->getName() : null;
                $action = method_exists($route, 'getActionName') ? $route->getActionName() : null;
                $middleware = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : [];
                $prefix = method_exists($route, 'getPrefix') ? (string) $route->getPrefix() : '';

                return [
                    'method' => implode('|', $methods),
                    'uri' => $uri,
                    'name' => $name,
                    'action' => is_string($action) ? $action : null,
                    'middleware' => implode(',', $middleware),
                    'prefix' => $prefix,
                ];
            })
            ->filter(function ($r) {
                $uri = $r['uri'] ?? '';
                $prefix = $r['prefix'] ?? '';
                $mw = $r['middleware'] ?? '';

                return str_starts_with($uri, 'api')
                    || str_starts_with($prefix, 'api')
                    || str_contains($mw, 'api');
            })
            ->values();

        $notifications = $routes->filter(function ($r) {
            $uri = $r['uri'] ?? '';

            return str_contains($uri, 'notify') || str_contains($uri, 'push');
        })->values();

        return [
            'endpoints' => $routes,
            'notifications' => $notifications,
            'totalCount' => $routes->count(),
            'notificationsCount' => $notifications->count(),
        ];
    }
}
