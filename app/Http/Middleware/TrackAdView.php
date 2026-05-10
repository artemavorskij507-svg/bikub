<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class TrackAdView
{
    /**
     * High-performance view counter using Redis HyperLogLog or simple Increment.
     * Prevents DB writes on every page load.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests to the show page that were successful
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $route = $request->route();

            // Check if route has 'ad' parameter (route model binding) or 'slug' parameter
            if ($route) {
                $slug = null;

                // Try to get slug from route model binding (ad:slug)
                if ($route->hasParameter('ad')) {
                    $ad = $route->parameter('ad');
                    $slug = $ad instanceof \App\Modules\Classifieds\Models\ClassifiedAd ? $ad->slug : null;
                } elseif ($route->hasParameter('slug')) {
                    $slug = $route->parameter('slug');
                }

                if ($slug) {
                    // Use IP + UserAgent hash to prevent simple refresh spam (deduplication key)
                    $visitorHash = md5($request->ip().$request->userAgent());
                    $key = "ad_views:{$slug}";

                    // Check if this visitor already viewed this ad in the last hour
                    $dedupKey = "view_dedup:{$slug}:{$visitorHash}";

                    if (! Redis::exists($dedupKey)) {
                        // Increment view counter in Redis
                        Redis::incr("ad_views_buffer:{$slug}");

                        // Set deduplication lock for 1 hour
                        Redis::setex($dedupKey, 3600, 1);
                    }
                }
            }
        }

        return $response;
    }
}
