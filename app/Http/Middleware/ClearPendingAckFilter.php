<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearPendingAckFilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->isMethod('GET')
            || $request->expectsJson()
            || $request->ajax()
            || $request->is('livewire/*')
        ) {
            return $next($request);
        }

        if (! $request->has('tableFilters')) {
            return $next($request);
        }

        $filters = $request->query('tableFilters', []);
        if (! is_array($filters)) {
            return $next($request);
        }

        $changed = false;

        if ($request->is('admin/work-specifications')) {
            $changed = $this->stripInactiveFilter($filters, 'pending_ack') || $changed;
        }

        if ($request->is('admin/schedule-slots')) {
            $changed = $this->stripInactiveFilter($filters, 'only_free') || $changed;
        }

        if ($request->is('admin/users')) {
            $changed = $this->stripInactiveFilter($filters, 'active') || $changed;
        }

        if ($request->is('admin/feature-flags')) {
            $changed = $this->stripInactiveFilter($filters, 'valid_now') || $changed;
        }

        if ($request->is('admin/pricing-rules')) {
            $changed = $this->stripInactiveFilter($filters, 'valid_now') || $changed;
        }

        if ($request->is('admin/eco-certificates') || $request->is('admin/eco-certificates/*')) {
            $changed = $this->stripInactiveFilter($filters, 'issued_at_recent') || $changed;
        }

        if ($request->is('admin/claims')) {
            $changed = $this->stripInactiveFilter($filters, 'sla_response_breached') || $changed;
            $changed = $this->stripInactiveFilter($filters, 'sla_resolution_breached') || $changed;
        }

        if ($request->is('admin/moving/executor-profiles')) {
            $changed = $this->stripInactiveFilter($filters, 'quality_issues') || $changed;
        }

        if ($request->is('admin/care-services') || $request->is('admin/care-services/*')) {
            $changed = $this->stripInactiveFilter($filters, 'has_plans') || $changed;
        }

        if ($request->is('admin/assistant-conversations') || $request->is('admin/assistant-conversations/*')) {
            $changed = $this->stripInactiveFilter($filters, 'has_messages') || $changed;
            $changed = $this->stripInactiveFilter($filters, 'no_messages') || $changed;
        }

        if ($request->is('admin/delivery/delivery-orders') || $request->is('admin/delivery/delivery-orders/*')) {
            $changed = $this->stripInactiveFilter($filters, 'is_urgent') || $changed;
            $changed = $this->stripInactiveFilter($filters, 'has_courier') || $changed;
        }

        if ($changed) {
            $url = empty($filters)
                ? $request->url()
                : $request->fullUrlWithQuery(['tableFilters' => $filters]);

            return redirect()->to($url);
        }

        return $next($request);
    }

    protected function stripInactiveFilter(array &$filters, string $key): bool
    {
        if (! isset($filters[$key]['isActive']) || (int) $filters[$key]['isActive'] !== 0) {
            return false;
        }

        unset($filters[$key]['isActive']);

        if (empty($filters[$key])) {
            unset($filters[$key]);
        }

        return true;
    }
}