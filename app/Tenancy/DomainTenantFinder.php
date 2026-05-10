<?php

namespace App\Tenancy;

use App\Models\Partner;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $domain = $request->getHost();

        try {
            return Partner::query()
                ->where('domain', $domain)
                ->where('is_active', true)
                ->first();
        } catch (QueryException $exception) {
            Log::warning('Tenant lookup skipped because the database connection is unavailable.', [
                'domain' => $domain,
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
