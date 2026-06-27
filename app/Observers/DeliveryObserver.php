<?php

namespace App\Observers;

use App\Models\Delivery;
use App\Models\DeliveryLog;
use App\Events\DeliveryStatusUpdated;

class DeliveryObserver
{
    // Part 7.1: Model Observer for automatic logging
    public function updated(Delivery $delivery): void
    {
        // Only log if the status actually changed (ignores unrelated updates)
        if ($delivery->wasChanged('status')) {
            DeliveryLog::create([
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
                'notes' => "Status changed from {$delivery->getOriginal('status')} to {$delivery->status}"
            ]);

            // Part 7.2: Real-time Status Updates via Broadcasting
            broadcast(new DeliveryStatusUpdated($delivery))->toOthers();
        }
    }
}
