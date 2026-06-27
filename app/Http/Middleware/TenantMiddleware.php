<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 5.2: Identify current tenant from request
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID is missing from headers.'], 400);
        }

        // 5.2: Efficient under high traffic (Cached lookup instead of Snippet B's DB call on every request)
        $tenant = Cache::remember('tenant_'.$tenantId, 3600, function () use ($tenantId) {
            return Tenant::find($tenantId);
        });

        if (!$tenant) {
            return response()->json(['error' => 'Invalid Tenant ID.'], 404);
        }

        // Make tenant available to the rest of the application
        $request->attributes->add(['tenant' => $tenant, 'tenant_id' => $tenant->id]);
        
        return $next($request);
    }
}
