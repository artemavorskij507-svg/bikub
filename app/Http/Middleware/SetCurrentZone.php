<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentZone
{
    /**
     * Route names that should bypass zone enforcement.
     */
    protected array $exceptRouteNames = [
        'home',
        'public.home',
        'public.cart.index',
        'public.cart.optimize',
        'public.store.show',
        'public.category',
        'public.service',
        'public.catalog.index',
        'public.roadside.index',
        'public.roadside.order',
        'public.roadside.order.submit',
        'public.roadside.thanks',
        'public.roadside.sos',
        'public.roadside.sos.submit',
        'public.roadside.sos.success',
        'classifieds.index',
        'classifieds.show',
        'classifieds.my-ads',
        'classifieds.create',
        'shops.show',
        'public.category.classifieds',
        'checkout.show',
        'checkout.store',
        'login',
        'register',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'two-factor.challenge',
        'api.v1.webhooks.stripe',
        'api.health',
        'language.switch',
        'language.switch.short',
        'admin', // Явно добавляем маршрут /admin
        'account.dashboard',
        'executor.dashboard',
        'lk.entry',
        'account.classifieds.delivery',
        'account.classifieds.sold',
        'account.classifieds.my-ads',
        'account.classifieds.create',
        'account.classifieds.store',
        'account.classifieds.update',
        'account.classifieds.destroy',
        'account.classifieds.edit',
        'partner.dashboard',
        'partner.orders',
        'partner.orders.status',
    ];

    /**
     * Path patterns that should bypass zone enforcement.
     */
    protected array $exceptPaths = [
        'filament/*',
        'admin',
        'admin/*',
        'api/*',
        'lk/*',
        'auth',
        'auth/*',
        'two-factor-challenge',
        'account',
        'account/*',
        'partner',
        'partner/*',
        'horizon',
        'horizon/*',
        'classifieds',
        'classifieds/*',
        'checkout',
        'checkout/*',
        'shops',
        'shops/*',
        'sitemap-classifieds.xml',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // АБСОЛЮТНО ПЕРША перевірка - пропускаємо адмінку, логін та Filament ДО будь-яких інших перевірок
        // Це критично, щоб Filament маршрути не потрапляли під перевірку current_zone_id
        $path = $request->path();
        $firstSegment = $request->segment(1);
        $uri = $request->getRequestUri();

        // КРИТИЧНО: Перевіряємо ПЕРШИМ, до будь-яких інших перевірок
        // Перевіряємо через кілька способів для надійності
        if ($firstSegment === 'admin' ||
            $firstSegment === 'filament' ||
            $firstSegment === 'api' ||
            $firstSegment === 'login' ||
            $firstSegment === 'logout' ||
            $firstSegment === 'register' ||
            $firstSegment === 'account' ||
            $firstSegment === 'partner' ||
            $firstSegment === 'auth' ||
            $firstSegment === 'lk' ||
            $firstSegment === 'horizon' ||
            $firstSegment === 'two-factor-challenge' ||
            str_starts_with($path, 'admin') ||
            str_starts_with($path, 'filament') ||
            str_starts_with($path, 'login') ||
            str_starts_with($path, 'logout') ||
            str_starts_with($path, 'account') ||
            str_starts_with($path, 'partner') ||
            str_starts_with($path, 'auth') ||
            str_starts_with($path, 'lk') ||
            str_starts_with($path, 'horizon') ||
            str_starts_with($path, 'two-factor-challenge') ||
            str_contains($uri, '/admin') ||
            str_contains($uri, '/filament') ||
            str_contains($uri, '/login') ||
            str_contains($uri, '/logout') ||
            str_contains($uri, '/account') ||
            str_contains($uri, '/partner') ||
            str_contains($uri, '/auth/') ||
            str_contains($uri, '/lk') ||
            str_contains($uri, '/horizon')) {
            return $next($request);
        }

        // Друга перевірка через shouldSkip
        if ($this->shouldSkip($request) || app()->runningUnitTests()) {
            return $next($request);
        }

        // Тільки після всіх перевірок перевіряємо current_zone_id
        if (! session()->has('current_zone_id')) {
            // НЕ перенаправляем, если это админка, Filament, API, ЛК или аутентификация
            $routeName = $request->route()?->getName() ?? '';
            $isFilamentRoute = str_starts_with($routeName, 'filament.');
            $isAdminRoute = str_starts_with($routeName, 'admin') || $routeName === 'admin';
            $isLkRoute = str_starts_with($routeName, 'lk.');
            $isExecutorRoute = str_starts_with($routeName, 'executor.');
            $isAuthRoute = in_array($routeName, ['login', 'logout', 'register', 'password.request', 'password.email', 'password.reset', 'password.update'], true);

            $isClassifiedsRoute = str_starts_with($routeName, 'classifieds.') ||
                                  str_starts_with($routeName, 'account.classifieds.') ||
                                  str_starts_with($routeName, 'shops.') ||
                                  $routeName === 'public.category.classifieds';

            // Все маршруты lk.* должны пропускаться
            $isAnyLkRoute = str_starts_with($routeName, 'lk.');

            if ($request->isMethod('get') &&
                ! $request->routeIs('home') &&
                ! $request->routeIs('public.home') &&
                ! $request->routeIs('public.catalog.index') &&
                ! $request->routeIs('public.category') &&
                ! $isFilamentRoute &&
                ! $isAdminRoute &&
                ! $isLkRoute &&
                ! $isAnyLkRoute &&
                ! $isExecutorRoute &&
                ! $isAuthRoute &&
                ! $isClassifiedsRoute) {
                session()->put('zone.intended_url', $request->fullUrl());

                return redirect()
                    ->route('public.catalog.index')
                    ->with('warning', 'Пожалуйста, выберите вашу зону обслуживания.');
            }
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        // Проверяем путь напрямую для админки и Filament
        $path = $request->path();
        if (str_starts_with($path, 'admin') ||
            str_starts_with($path, 'filament') ||
            str_starts_with($path, 'login') ||
            str_starts_with($path, 'logout') ||
            str_starts_with($path, 'auth') ||
            str_starts_with($path, 'account') ||
            str_starts_with($path, 'partner') ||
            str_starts_with($path, 'lk') ||
            str_starts_with($path, 'horizon') ||
            str_starts_with($path, 'two-factor-challenge') ||
            str_starts_with($path, 'classifieds') ||
            str_starts_with($path, 'checkout') ||
            str_starts_with($path, 'shops') ||
            str_starts_with($path, 'category') ||
            str_starts_with($path, 'sitemap-classifieds')) {
            return true;
        }

        $route = $request->route();

        if (! $route) {
            return true;
        }

        $name = $route->getName();

        // Проверяем имя маршрута Filament, админки и ЛК
        if ($name && (
            str_starts_with($name, 'filament.') ||
            str_starts_with($name, 'admin') ||
            str_starts_with($name, 'lk.') ||
            str_starts_with($name, 'account.') ||
            str_starts_with($name, 'partner.') ||
            in_array($name, $this->exceptRouteNames, true)
        )) {
            return true;
        }

        foreach ($this->exceptPaths as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
