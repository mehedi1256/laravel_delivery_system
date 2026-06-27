<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Delivery;
use Exception;

class SendDeliveryNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public function __construct(public Delivery $delivery) {}

    // Part 6.2: Retry & Failure Handling (Exponential backoff)
    public function backoff(): array
    {
        return [10, 20, 40, 80]; // Delay grows with each attempt
    }

    public function handle(): void
    {
        // Simulate a brittle third-party API
        $apiUnavailable = (rand(1, 10) > 8); 
        
        if ($apiUnavailable) {
            throw new Exception("Third-party API temporarily unavailable.");
        }
        
        // Success logic here...
    }

    public function failed(?Exception $exception): void
    {
        // When all retries are exhausted
        Log::critical("Alert: Delivery notification completely failed for Delivery ID: {$this->delivery->id}", [
            'exception' => $exception->getMessage()
        ]);
    }
}
