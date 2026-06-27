<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Delivery;
use App\Jobs\SendDeliveryNotificationJob;

class SendDeliveryNotificationsCommand extends Command
{
    protected $signature = 'deliveries:notify';
    protected $description = 'Send a notification for all delivery records memory-safely';

    public function handle()
    {
        // Part 8.2: Fixing a Memory-leaking Command (disable query logs)
        \DB::connection()->disableQueryLog();

        // Part 3.1: Memory-safe Data Processing (use cursor instead of get)
        foreach (Delivery::cursor() as $delivery) {
            dispatch(new SendDeliveryNotificationJob($delivery));
            
            // Explicit memory management
            unset($delivery);
        }

        $this->info('Notifications dispatched successfully.');
    }
}
