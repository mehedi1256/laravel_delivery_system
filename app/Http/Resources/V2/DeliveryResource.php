<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Part 5.1: In v2, the user field is restructured as assigned_agent
            'assigned_agent' => [
                'agent_id' => $this->user->id ?? null,
                'full_name' => $this->user->name ?? null,
                'contact_number' => $this->user->phone ?? 'N/A', 
                'vehicle_type' => 'motorcycle', // mock extra fields
            ],
            'status' => $this->status,
        ];
    }
}
