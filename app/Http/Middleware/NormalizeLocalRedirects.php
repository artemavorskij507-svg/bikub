<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeLocalRedirects
{
    /**
     * Keep local redirects on the current host:port by converting
     * absolute localhost/127.0.0.1 redirects without explicit port to relative URLs.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! app()->environment('local')) {
            return $response;
        }

        $location = $response->headers->get('Location');
        if (! is_string($location) || $location === '') {
            return $response;
        }

        $parts = parse_url($location);
        if (! is_array($parts)) {
            return $response;
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        if (! in_array($host, ['127.0.0.1', 'localhost'], true) || $port !== null) {
            return $response;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? ('?'.$parts['query']) : '';
        $fragment = isset($parts['fragment']) ? ('#'.$parts['fragment']) : '';

        if ($path === '') {
            $path = '/';
        }

        $response->headers->set('Location', $path.$query.$fragment);

        return $response;
    }
}

