<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Delivery;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $tenantIds = DB::table('tenants')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        if (empty($tenantIds) || empty($userIds)) {
            return;
        }

        $deliveries = [];
        for ($i = 0; $i < 50; $i++) {
            // Generate a random date in the last 3 months
            $randomDate = now()->subDays(rand(1, 90));
            $updatedDate = clone $randomDate;
            $updatedDate->addMinutes(rand(30, 300)); // 30 mins to 5 hours delivery time

            $deliveries[] = [
                'tenant_id' => $tenantIds[array_rand($tenantIds)],
                'user_id' => $userIds[array_rand($userIds)],
                'pickup_address' => $faker->address,
                'delivery_address' => $faker->address,
                'status' => $faker->randomElement(['delivered', 'delivered', 'delivered', 'failed']),
                'created_at' => $randomDate,
                'updated_at' => $updatedDate,
            ];
        }

        DB::table('deliveries')->insert($deliveries);
    }
}
