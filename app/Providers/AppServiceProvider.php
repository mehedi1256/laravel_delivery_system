<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Part 5.2: Dynamic Rate Limiting based on Tenant subscription plan
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            $tenant = $request->attributes->get('tenant');
            
            if (!$tenant) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip()); // Default
            }

            // Vary rate limit by subscription plan
            $limit = match($tenant->subscription_plan) {
                'enterprise' => 1000,
                'premium'    => 500,
                default      => 100, // basic
            };

            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($tenant->id);
        });
    }
}
