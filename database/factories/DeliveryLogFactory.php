<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Delivery;

class DeliveryLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_transit', 'delivered', 'failed']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
