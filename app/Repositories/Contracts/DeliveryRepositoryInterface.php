<?php

namespace App\Repositories\Contracts;

interface DeliveryRepositoryInterface
{
    /**
     * Retrieve all deliveries for a given user, including the latest log entry and count.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeliveriesForUser(int $userId);

    // Other standard repository methods can be added here
}
