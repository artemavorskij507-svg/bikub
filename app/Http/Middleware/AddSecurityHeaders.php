<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $isLocalRuntime = app()->environment('local') || in_array($request->getHost(), ['127.0.0.1', 'localhost'], true);

        $scriptSrc = [
            "'self'",
            'https://js.stripe.com',
            'https://cdn.tailwindcss.com',
            'https://unpkg.com',
            'https://api.mapbox.com',
            "'unsafe-inline'",
            "'unsafe-eval'",
            'blob:', // для Livewire file uploads
        ];

        $connectSrc = [
            "'self'",
            'https://api.stripe.com',
            'https://api.mapbox.com',
            'ws:',
            'wss:',
            'blob:',
            'data:',
        ];

        // Ініціалізуємо script-src-elem
        $scriptSrcElem = $scriptSrc;

        if ($isLocalRuntime) {
            // Fallback for local absolute URLs that may be generated without explicit port.
            $scriptSrc[] = 'http://127.0.0.1';
            $scriptSrc[] = 'http://localhost';
            $connectSrc[] = 'http://127.0.0.1';
            $connectSrc[] = 'http://localhost';
            // Vite dev server ports (може працювати на різних портах 5173-5180)
            // IPv6 адреси [::1] не підтримуються в CSP, використовуємо тільки IPv4
            // Додаємо діапазон портів для підтримки динамічних портів Vite
            for ($port = 5173; $port <= 5180; $port++) {
                $scriptSrc[] = "http://localhost:{$port}";
                $scriptSrc[] = "http://127.0.0.1:{$port}";
                $connectSrc[] = "http://localhost:{$port}";
                $connectSrc[] = "http://127.0.0.1:{$port}";
                $connectSrc[] = "ws://localhost:{$port}";
                $connectSrc[] = "ws://127.0.0.1:{$port}";
                $connectSrc[] = "wss://localhost:{$port}";
                $connectSrc[] = "wss://127.0.0.1:{$port}";
            }
            // Додаємо unsafe-inline для script-src-elem в dev режимі для Vite HMR
            $scriptSrcElem = array_merge($scriptSrc, ["'unsafe-inline'"]);
        }

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://fonts.bunny.net',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://api.mapbox.com',
        ];

        $styleSrcElem = $styleSrc;

        if ($isLocalRuntime) {
            // Fallback for local absolute URLs that may be generated without explicit port.
            $styleSrc[] = 'http://127.0.0.1';
            $styleSrc[] = 'http://localhost';
            $styleSrcElem[] = 'http://127.0.0.1';
            $styleSrcElem[] = 'http://localhost';
            for ($port = 5173; $port <= 5180; $port++) {
                $styleSrc[] = "http://localhost:{$port}";
                $styleSrc[] = "http://127.0.0.1:{$port}";
                $styleSrcElem[] = "http://localhost:{$port}";
                $styleSrcElem[] = "http://127.0.0.1:{$port}";
            }
        }

        $csp = "default-src 'self' blob:; ".
               'script-src '.implode(' ', $scriptSrc).'; '.
               'script-src-elem '.implode(' ', $scriptSrcElem).'; '.
               "worker-src 'self' blob:; ". // для Livewire file upload workers
               'connect-src '.implode(' ', $connectSrc).'; '.
               "img-src 'self' data: https: blob:; ".
               "media-src 'self' blob: data:; ".
               'style-src '.implode(' ', $styleSrc).'; '.
               'style-src-elem '.implode(' ', $styleSrcElem).'; '.
               "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net https://cdnjs.cloudflare.com; ".
               'frame-src https://js.stripe.com https://hooks.stripe.com; '.
               "object-src 'none'; ".
               "base-uri 'self'; ".
               "form-action 'self'; ".
               "frame-ancestors 'self';";

        if ($isLocalRuntime) {
            // Local/dev: keep diagnostics, but do not block page rendering because of browser extensions
            // or temporary proxy host/port mismatches.
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
            $response->headers->remove('Content-Security-Policy');
        } else {
            $response->headers->set('Content-Security-Policy', $csp);
            $response->headers->remove('Content-Security-Policy-Report-Only');
        }

        if (app()->environment('production') || $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');
        $response->headers->set('X-Debug-Host', (string) $request->getHost());
        $response->headers->set('X-Debug-LocalRuntime', $isLocalRuntime ? '1' : '0');

        return $response;
    }
}


