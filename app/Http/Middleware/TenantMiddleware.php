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

        // We are temporarily bypassing the cache because your local PHP server 
        // has corrupted memory of the Tenant object.
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Invalid Tenant ID.'], 404);
        }

        // Make tenant available to the rest of the application
        $request->attributes->add(['tenant' => $tenant, 'tenant_id' => $tenant->id]);
        
        return $next($request);
    }
}
