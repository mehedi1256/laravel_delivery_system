<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Delivery;
use Exception;

class ProcessCsvRowJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $row, public int $tenantId) {}

    public function handle(): void
    {
        // Part 6.1: Check if batch was cancelled
        if ($this->batch()->cancelled()) {
            return;
        }

        // Simulate processing the row.
        // If a row fails (e.g., missing data), we throw an exception.
        // Because the batch allows failures, it will register the failure 
        // without aborting the entire 5,000 row import batch!
        if (empty($this->row['user_id'])) {
            throw new Exception("Missing user ID in row.");
        }

        Delivery::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->row['user_id'],
            'status' => 'pending',
            'pickup_address' => $this->row['pickup_address'],
            'delivery_address' => $this->row['delivery_address'],
        ]);
    }
}
