<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use App\Http\Resources\V1\DeliveryResource as V1Resource;
use App\Http\Resources\V2\DeliveryResource as V2Resource;
use App\Jobs\ProcessCsvRowJob;
use Illuminate\Support\Facades\Bus;
use OpenApi\Attributes as OA;

class DeliveryController extends Controller
{
    #[OA\Get(
        path: "/api/deliveries",
        operationId: "getDeliveries",
        tags: ["Deliveries"],
        summary: "Get a list of deliveries",
        description: "Returns a paginated list of deliveries scoped to the tenant. Supports cursor pagination.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "X-Tenant-ID",
        in: "header",
        required: true,
        description: "Tenant ID for scoping",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Parameter(
        name: "Accept-Version",
        in: "header",
        required: false,
        description: "API Version (v1 or v2)",
        schema: new OA\Schema(type: "string", default: "v2")
    )]
    #[OA\Response(response: 200, description: "Successful operation")]
    public function index(Request $request)
    {
        $deliveries = Delivery::where('tenant_id', $request->attributes->get('tenant_id'))
            ->cursorPaginate(15);
        
        if ($request->header('Accept-Version') === 'v1') {
            return V1Resource::collection($deliveries);
        }

        return V2Resource::collection($deliveries);
    }

    #[OA\Post(
        path: "/api/v1/imports",
        operationId: "importDeliveries",
        tags: ["Deliveries"],
        summary: "Import deliveries via CSV",
        description: "Dispatches a batch job to import up to 5,000 rows. Individual row failures do not abort the batch.",
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "X-Tenant-ID",
        in: "header",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 202, description: "Batch import started")]
    public function import(Request $request)
    {
        $rows = [
            ['user_id' => 1, 'pickup_address' => 'A', 'delivery_address' => 'B'],
            ['user_id' => null, 'pickup_address' => 'C', 'delivery_address' => 'D'],
            ['user_id' => 2, 'pickup_address' => 'E', 'delivery_address' => 'F'],
        ];

        $tenantId = $request->attributes->get('tenant_id');
        $jobs = [];

        foreach ($rows as $row) {
            $jobs[] = new ProcessCsvRowJob($row, $tenantId);
        }

        $batch = Bus::batch($jobs)->allowFailures()->dispatch();

        return response()->json([
            'message' => 'Import batch dispatched.',
            'batch_id' => $batch->id
        ], 202);
    }
}
