<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Delivery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ExportDeliveriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $tenantId) {}

    public function handle(): void
    {
        $filename = "exports/deliveries_tenant_{$this->tenantId}_" . time() . ".csv";
        
        // Ensure directory exists
        if (!Storage::disk('local')->exists('exports')) {
            Storage::disk('local')->makeDirectory('exports');
        }

        $file = fopen(storage_path("app/private/{$filename}"), 'w');
        
        fputcsv($file, ['ID', 'User ID', 'Status', 'Pickup Address', 'Delivery Address', 'Created At']);

        // Part 4.1: Memory safe streaming export using chunk
        Delivery::where('tenant_id', $this->tenantId)->chunk(1000, function ($deliveries) use ($file) {
            foreach ($deliveries as $delivery) {
                fputcsv($file, [
                    $delivery->id,
                    $delivery->user_id,
                    $delivery->status,
                    $delivery->pickup_address,
                    $delivery->delivery_address,
                    $delivery->created_at,
                ]);
            }
        });

        fclose($file);

        // Generate temporary expiring link (1 hour)
        $url = URL::temporarySignedRoute(
            'download.export', now()->addHour(), ['file' => $filename]
        );

        // Notify user about the download $url (via notification service)...
    }
}
