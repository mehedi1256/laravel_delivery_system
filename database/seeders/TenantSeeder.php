<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $plans = ['basic', 'premium', 'enterprise'];

        $tenants = [];
        for ($i = 0; $i < 20; $i++) {
            $tenants[] = [
                'name' => $faker->company . ' Delivery',
                'subscription_plan' => $plans[array_rand($plans)],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('tenants')->insert($tenants);
    }
}
