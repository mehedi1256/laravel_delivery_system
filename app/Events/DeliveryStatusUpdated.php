<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Broadcast immediately
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Delivery;

class DeliveryStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Delivery $delivery) {}

    public function broadcastOn(): array
    {
        // Part 7.2: Channel authorization (Private channel for the assigned driver/user)
        return [
            new PrivateChannel("deliveries.{$this->delivery->id}"),
            new PrivateChannel("users.{$this->delivery->user_id}.deliveries"),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'status' => $this->delivery->status,
        ];
    }
}
