<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Delivery;
use App\Models\DeliveryLog;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
        ]);

        $faker = Faker::create();
        
        // Let's get some tenant IDs
        $tenantIds = DB::table('tenants')->pluck('id')->toArray();
        if (empty($tenantIds)) {
            return;
        }

        // Generate Users
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'tenant_id' => $tenantIds[array_rand($tenantIds)],
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
            ]);
        }

        $userIds = User::pluck('id')->toArray();

        // Generate Deliveries
        for ($i = 0; $i < 100; $i++) {
            $delivery = Delivery::create([
                'tenant_id' => $tenantIds[array_rand($tenantIds)],
                'user_id' => $userIds[array_rand($userIds)],
                'pickup_address' => $faker->address,
                'delivery_address' => $faker->address,
                'status' => $faker->randomElement(['pending', 'in_transit', 'delivered', 'failed']),
            ]);

            // Generate Logs for this delivery
            for ($j = 0; $j < rand(1, 3); $j++) {
                DeliveryLog::create([
                    'delivery_id' => $delivery->id,
                    'status' => $faker->randomElement(['pending', 'in_transit']),
                    'notes' => $faker->sentence,
                ]);
            }
        }
    }
}
