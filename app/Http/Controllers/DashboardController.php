<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: "/api/reports/dashboard",
        operationId: "getDashboard",
        tags: ["Reports"],
        summary: "Get dashboard statistics",
        description: "Returns aggregated delivery statistics scoped by tenant and grouped by week. Result is cached.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "X-Tenant-ID",
        in: "header",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 200, description: "Successful operation")]
    public function index()
    {
        $tenantId = request()->attributes->get('tenant_id');

        $cacheKey = 'tenant_'.$tenantId.'_dashboard';

        $stats = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return DB::table('deliveries')
                ->select(
                    DB::raw("DATE_TRUNC('week', created_at) as week"),
                    DB::raw("COUNT(id) as total_deliveries"),
                    DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(id), 0) as success_rate"),
                    DB::raw("AVG(EXTRACT(EPOCH FROM (updated_at - created_at))) as avg_delivery_time_seconds")
                )
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subMonths(3))
                ->groupBy('week')
                ->orderBy('week', 'desc')
                ->get();
        });

        return response()->json($stats);
    }
}
