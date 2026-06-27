<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'status' => $this->status,
        ];
    }

    // Part 5.1: v1 should communicate to clients that it is deprecated.
    public function withResponse($request, $response)
    {
        $response->header('Deprecation', 'true');
        $response->header('Link', '<https://api.example.com/v2/deliveries>; rel="successor-version"');
    }
}
