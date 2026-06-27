<?php

namespace App\Repositories\Eloquent;

use App\Models\Delivery;
use App\Repositories\Contracts\DeliveryRepositoryInterface;

class DeliveryRepository implements DeliveryRepositoryInterface
{
    protected $model;

    public function __construct(Delivery $model)
    {
        $this->model = $model;
    }

    public function getDeliveriesForUser(int $userId)
    {
        // 1.2 — Relationships & Querying
        // "a single query that retrieves all deliveries for a given user, 
        // including the latest log entry for each delivery and a count of total logs."
        return $this->model->where('user_id', $userId)
            ->with('logs', function ($query) {
                $query->latest()->limit(1);
            })
            ->withCount('logs')
            ->get();
    }
}
