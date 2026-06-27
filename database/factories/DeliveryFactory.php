<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;
use App\Models\User;

class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        // Part 9.2: Bangladesh realistic data
        
        // Bangladesh local phone numbers (e.g. +88017XXXXXXXX)
        $operatorCode = $this->faker->randomElement(['13', '14', '15', '16', '17', '18', '19']);
        $phoneNumber = '+880' . $operatorCode . $this->faker->numerify('########');

        // Timestamps mapped to status logically
        $createdAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $updatedAt = (clone $createdAt)->modify('+' . rand(1, 48) . ' hours');

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_transit', 'delivered', 'failed']),
            'pickup_address' => 'House ' . rand(1, 50) . ', Road ' . rand(1, 20) . ', Banani, Dhaka, Bangladesh',
            'delivery_address' => 'House ' . rand(1, 50) . ', Road ' . rand(1, 20) . ', Dhanmondi, Dhaka, Bangladesh',
            'recipient_phone' => $phoneNumber,
            // Dhaka area bounds roughly: Lat 23.70 to 23.90, Lng 90.35 to 90.45
            'latitude' => $this->faker->latitude(23.7, 23.9),
            'longitude' => $this->faker->longitude(90.35, 90.45),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
