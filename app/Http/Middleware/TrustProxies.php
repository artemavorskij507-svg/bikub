<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Trust all proxies in production (behind load balancer)

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers;

    public function __construct()
    {
        // Local reverse-proxy stacks often send X-Forwarded-Host without port.
        // That makes Laravel generate absolute URLs like http://127.0.0.1/login
        // instead of http://127.0.0.1:2244/login.
        if (app()->environment('local')) {
            $this->headers =
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_PROTO;

            return;
        }

        $this->headers =
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB;
    }
}
