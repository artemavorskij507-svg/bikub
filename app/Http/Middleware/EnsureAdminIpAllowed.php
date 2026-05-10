<?php

namespace App\Http\Middleware;

use App\Models\AdminIpRule;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIpAllowed
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getPathInfo();

        if (! str_starts_with($path, '/admin') && ! str_starts_with($path, '/filament')) {
            return $next($request);
        }

        // Prevent accidental lockout during local development/demo.
        if (app()->environment('local')) {
            return $next($request);
        }

        // Check if the admin_ip_rules table exists (during initial setup, it might not)
        if (! Schema::hasTable('admin_ip_rules')) {
            return $next($request);
        }

        try {
            $ip = $request->ip();

            // Evaluate rules: if any active deny matches -> deny. Else if any active allow exists and matches -> allow. Else if allowlist exists -> deny.
            $rules = AdminIpRule::where('is_active', true)->get();

            if ($rules->isEmpty()) {
                // No rules defined, allow all
                return $next($request);
            }

            $denyMatch = $rules->where('type', 'deny')->first(fn ($r) => $this->ipMatches($ip, $r->ip_range));
            if ($denyMatch) {
                // log denied attempt
                try {
                    AuditLog::create([
                        'actor_user_id' => optional($request->user())->id,
                        'action' => 'admin_ip_denied',
                        'model_type' => 'admin_ip_rules',
                        'model_id' => $denyMatch->id,
                        'before' => null,
                        'after' => null,
                        'ip_address' => $ip,
                        'user_agent' => $request->userAgent(),
                        'request_id' => $request->attributes->get('request_id'),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail audit logging
                }

                return response('Access denied', Response::HTTP_FORBIDDEN);
            }

            $allowRules = $rules->where('type', 'allow');
            if ($allowRules->isNotEmpty()) {
                $allowed = $allowRules->first(fn ($r) => $this->ipMatches($ip, $r->ip_range));
                if (! $allowed) {
                    try {
                        AuditLog::create([
                            'actor_user_id' => optional($request->user())->id,
                            'action' => 'admin_ip_not_allowed',
                            'model_type' => 'admin_ip_rules',
                            'model_id' => null,
                            'before' => null,
                            'after' => null,
                            'ip_address' => $ip,
                            'user_agent' => $request->userAgent(),
                            'request_id' => $request->attributes->get('request_id'),
                        ]);
                    } catch (\Exception $e) {
                        // Silently fail audit logging
                    }

                    return response('Access denied', Response::HTTP_FORBIDDEN);
                }
            }
        } catch (\Exception $e) {
            // If any error occurs during IP rule checking, allow through (fail open for usability)
            // Log the error for debugging if needed
            if (config('app.debug')) {
                \Log::warning('EnsureAdminIpAllowed middleware error: '.$e->getMessage());
            }
        }

        return $next($request);
    }

    protected function ipMatches(string $ip, string $range): bool
    {
        // simple CIDR or exact ip match
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - (int) $bits);

            return ($ip & $mask) === ($subnet & $mask);
        }

        return $ip === $range;
    }
}